<?php
// ARQUIVO: dados_bonus.php (A Cozinha) - VERSÃO 100% BANCO DE DADOS E COMPLETA

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- FUNÇÕES AUXILIARES ---
function fmtMoney($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function parseDateGuess($s) { 
    $s = trim((string)$s); 
    if (!$s) return false; 
    $formats = ['d/m/Y', 'Y-m-d']; 
    foreach ($formats as $f) { 
        $dt = DateTime::createFromFormat($f, $s); 
        if ($dt !== false) { 
            $dt->setTime(0,0,0); 
            return $dt;
        }
    } 
    return false; 
}

// --- PERFIL E FILTROS ---
$isGestor = in_array($usuario_logado['tipo'], ['admin', 'usuario_gestor', 'usuario_gerente']);
date_default_timezone_set('America/Sao_Paulo');
$hoje = new DateTime();
$filterConsultor = trim($_GET['consultor'] ?? '');
$filterEquipe = trim($_GET['equipe'] ?? '');
$filterContrato = trim($_GET['filter_contrato'] ?? '');
$filterDataInicio = !empty($_GET['data_inicio']) ? DateTime::createFromFormat('Y-m-d', $_GET['data_inicio']) : (new DateTime('first day of this month'));
$filterDataFim = !empty($_GET['data_fim']) ? DateTime::createFromFormat('Y-m-d', $_GET['data_fim']) : (new DateTime('last day of this month'));
$filterDataInicio->setTime(0,0,0);
$filterDataFim->setTime(23,59,59);

// --- INICIALIZAÇÃO DE VARIÁVEIS ---
$totais = ['ganha'=>0.0, 'perdida'=>0.0, 'em_potencial'=>0.0, 'mes_atual'=>0.0, 'ult_cancelados'=>0.0];
$pie = ['em_dia'=>0.0, 'em_atraso'=>0.0, 'cancelado'=>0.0];
$monthsMap = [];
$gsError = '';
$consultoresUnicos = [];
$equipesUnicas = [];
$counts = ['rows_total'=>0, 'rows_filtered'=>0];

try {
    // --- BUSCA LISTAS COMPLETAS PARA OS FILTROS DIRETAMENTE DO BANCO ---
    $res_consultores = $conexao->query("SELECT DISTINCT nome_consultor FROM contratos WHERE nome_consultor IS NOT NULL AND nome_consultor != '' AND (status_atual IS NULL OR LOWER(TRIM(status_atual)) NOT LIKE 'exclu%') ORDER BY nome_consultor ASC");
    if ($res_consultores) { while ($linha = $res_consultores->fetch_assoc()) { $consultoresUnicos[] = $linha['nome_consultor']; } }
    $res_equipes = $conexao->query("SELECT DISTINCT equipe FROM contratos WHERE equipe IS NOT NULL AND equipe != '' AND (status_atual IS NULL OR LOWER(TRIM(status_atual)) NOT LIKE 'exclu%') ORDER BY equipe ASC");
    if ($res_equipes) { while ($linha = $res_equipes->fetch_assoc()) { $equipesUnicas[] = $linha['equipe']; } }

    // ===================================================================
    // FASE 1: DADOS HISTÓRICOS (DO BANCO - PARA "CONFIRMADO")
    // ===================================================================
    $where_clauses_hist = []; $params_hist = []; $types_hist = "";
    if ($usuario_logado['tipo'] === 'usuario_padrao') { $where_clauses_hist[] = "h.id_usuario = ?"; $params_hist[] = $usuario_logado['id']; $types_hist .= "i"; }
    elseif ($usuario_logado['tipo'] === 'usuario_gerente') { $where_clauses_hist[] = "u.equipe = ?"; $params_hist[] = $usuario_logado['equipe']; $types_hist .= "s"; }
    $where_clauses_hist[] = "h.data_pagamento_parcela BETWEEN ? AND ?";
    $params_hist[] = $filterDataInicio->format('Y-m-d'); $params_hist[] = $filterDataFim->format('Y-m-d'); $types_hist .= "ss";
    if ($filterConsultor) { $where_clauses_hist[] = "u.nome = ?"; $params_hist[] = $filterConsultor; $types_hist .= "s"; }
    if ($filterEquipe) { $where_clauses_hist[] = "u.equipe = ?"; $params_hist[] = $filterEquipe; $types_hist .= "s"; }
    if ($filterContrato) { $where_clauses_hist[] = "h.numero_contrato = ?"; $params_hist[] = $filterContrato; $types_hist .= "s"; }
    
    $sql_hist = "SELECT h.valor_comissao, h.data_pagamento_parcela, h.status_no_pagamento, u.nome as nome_consultor, u.equipe 
                 FROM historico_comissoes h JOIN usuarios u ON h.id_usuario = u.id WHERE " . implode(' AND ', $where_clauses_hist);

    $stmt_hist = $conexao->prepare($sql_hist);
    if (!empty($types_hist)) { $stmt_hist->bind_param($types_hist, ...$params_hist); }
    $stmt_hist->execute();
    $resultado_hist = $stmt_hist->get_result();
    $dataset_historico = [];
    while($linha = $resultado_hist->fetch_assoc()) { $dataset_historico[] = $linha; }
    $stmt_hist->close();

    $inicioGrafico = new DateTime('first day of january ' . $filterDataInicio->format('Y'));
    $fimGrafico = (clone $inicioGrafico)->modify('+11 months');
    $period = new DatePeriod($inicioGrafico, new DateInterval('P1M'), (clone $fimGrafico)->modify('+1 month'));
    foreach ($period as $dt) { $monthsMap[$dt->format('Y-m')] = 0.0; }

    $primeiroDiaMesAtual = new DateTime('first day of this month');
    $ultimoDiaMesAtual = new DateTime('last day of this month');

    foreach ($dataset_historico as $linha) {
        $status = strtolower($linha['status_no_pagamento']);
        $comissao = (float)$linha['valor_comissao'];
        $dataPagamento = new DateTime($linha['data_pagamento_parcela']);
        if ($status === 'em dia') {
            $totais['ganha'] += $comissao;
            $pie['em_dia'] += $comissao;
            $k = $dataPagamento->format('Y-m');
            if(isset($monthsMap[$k])) { $monthsMap[$k] += $comissao; }
            if ($dataPagamento >= $primeiroDiaMesAtual && $dataPagamento <= $ultimoDiaMesAtual) {
                $totais['mes_atual'] += $comissao;
            }
        }
    }
    
    // ===================================================================
    // FASE 2: DADOS 'AO VIVO' (DO BANCO - PARA "POTENCIAL" E "PERDIDO")
    // ===================================================================
    $comissoes_leve = [ 1 => 0.0022, 2 => 0.0012, 3 => 0.0012, 4 => 0.0012, 5 => 0.0011, 6 => 0.0011 ];
    $comissoes_pesado = [ 1 => 0.0012, 2 => 0.0008, 3 => 0.0010, 4 => 0.0012, 5 => 0.0012, 6 => 0.0014 ];

    $where_clauses_vivo = []; $params_vivo = []; $types_vivo = "";
    if ($usuario_logado['tipo'] === 'usuario_padrao') { $where_clauses_vivo[] = "id_usuario = ?"; $params_vivo[] = $usuario_logado['id']; $types_vivo .= "i"; }
    elseif ($usuario_logado['tipo'] === 'usuario_gerente') { $where_clauses_vivo[] = "equipe = ?"; $params_vivo[] = $usuario_logado['equipe']; $types_vivo .= "s"; }
    if ($filterConsultor) { $where_clauses_vivo[] = "nome_consultor = ?"; $params_vivo[] = $filterConsultor; $types_vivo .= "s"; }
    if ($filterEquipe) { $where_clauses_vivo[] = "equipe = ?"; $params_vivo[] = $filterEquipe; $types_vivo .= "s"; }
    if ($filterContrato) { $where_clauses_vivo[] = "numero_contrato = ?"; $params_vivo[] = $filterContrato; $types_vivo .= "s"; }
    $where_clauses_vivo[] = "(status_atual IS NULL OR LOWER(TRIM(status_atual)) NOT LIKE 'exclu%')";
    $where_clauses_vivo[] = "(status_atual LIKE '%Atraso%' OR status_atual LIKE '%Cancelad%')";
    
    $sql_vivo = "SELECT * FROM contratos WHERE " . implode(' AND ', $where_clauses_vivo);
    $stmt_vivo = $conexao->prepare($sql_vivo);
    if (!empty($types_vivo)) { $stmt_vivo->bind_param($types_vivo, ...$params_vivo); }
    $stmt_vivo->execute();
    $resultado_vivo = $stmt_vivo->get_result();

    $inicio90dias = (clone $hoje)->modify('-89 days')->setTime(0,0,0);
    $fim90dias = (clone $hoje)->setTime(23,59,59);

    while($r = $resultado_vivo->fetch_assoc()) {
        $situacao = strtolower($r['status_atual']);
        $parcelas_pagas = (int)$r['parcelas_pagas_atual'];
        $dataVenda = new DateTime($r['data_venda']);
        $valorCota = (float)$r['valor_cota'];
        $tabela = $r['tabela'];

        if ($valorCota <= 0 || empty($tabela) || $parcelas_pagas >= 6) continue;
        $tabelaComissao = (strcasecmp($tabela, 'Leve') === 0) ? $comissoes_leve : $comissoes_pesado;

        if (strpos($situacao, 'atraso') !== false) {
            for ($p = $parcelas_pagas + 1; $p <= 6; $p++) {
                $dataPagamentoPotencial = (clone $dataVenda)->modify('+' . ($p - 1) . ' months');
                if ($dataPagamentoPotencial >= $filterDataInicio && $dataPagamentoPotencial <= $filterDataFim) {
                    $comissaoDaParcela = $valorCota * ($tabelaComissao[$p] ?? 0);
                    $totais['em_potencial'] += $comissaoDaParcela;
                    $pie['em_atraso'] += $comissaoDaParcela;
                }
            }
        } elseif (strpos($situacao, 'cancel') !== false) {
            $comissao_perdida_total_futura = 0;
            $comissao_perdida_no_mes = 0;
            for ($p = $parcelas_pagas + 1; $p <= 6; $p++) {
                $comissaoDaParcela = $valorCota * ($tabelaComissao[$p] ?? 0);
                $comissao_perdida_total_futura += $comissaoDaParcela;
                $dataPagamentoPotencial = (clone $dataVenda)->modify('+' . ($p - 1) . ' months');
                if ($dataPagamentoPotencial >= $filterDataInicio && $dataPagamentoPotencial <= $filterDataFim) {
                    $comissao_perdida_no_mes += $comissaoDaParcela;
                }
            }
            $totais['perdida'] += $comissao_perdida_no_mes;
            $pie['cancelado'] += $comissao_perdida_no_mes;
            if ($dataVenda >= $inicio90dias && $dataVenda <= $fim90dias) {
                $totais['ult_cancelados'] += $comissao_perdida_total_futura;
            }
        }
    }
    $stmt_vivo->close();
    
    // --- FINALIZAÇÃO ---
    $counts = ['rows_total' => count($dataset_historico), 'rows_filtered' => count($dataset_historico)];
    $chartMonthsLabels=[]; $chartMonthsData=[];
    foreach($monthsMap as $key => $value){ $dt = DateTime::createFromFormat('Y-m',$key); $chartMonthsLabels[]=$dt?$dt->format('m/Y'):$key; $chartMonthsData[]=round($value,2); }
    $pieData=[round($pie['em_dia'],2), round($pie['em_atraso'],2), round($pie['cancelado'],2)];

} catch (Throwable $t) {
    $gsError = "ERRO GERAL: " . $t->getMessage();
    $totais = ['ganha'=>0.0, 'perdida'=>0.0, 'em_potencial'=>0.0, 'mes_atual'=>0.0, 'ult_cancelados'=>0.0];
    $pieData = []; $chartMonthsLabels = []; $chartMonthsData = [];
    $consultoresUnicos = []; $equipesUnicas = [];
}
?>
