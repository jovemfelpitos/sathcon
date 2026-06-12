<?php
// ARQUIVO: dados_efetividade.php - VERSÃO FINAL LENDO DO BANCO DE DADOS

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

$isOperador = $usuario_eh_operador ?? ($usuario_logado['tipo'] === 'usuario_padrao');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- FUNÇÃO AUXILIAR ---
function parseCurrency($v) {
    if (!$v) return 0.0;
    $v = preg_replace('/[^\d\.,-]/','', (string)$v);
    if (strpos($v, ',') !== false) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    return floatval($v);
}

try {
    // --- Captura dos Filtros da Interface ---
    $consultorFiltro = $_GET['consultor'] ?? '';
    $equipeFiltro = $_GET['equipe'] ?? '';
    $anoFiltro = $_GET['ano'] ?? '';
    $seguroFiltro = $_GET['seguro'] ?? '';
    $tabelaFiltro = $_GET['tabela'] ?? '';

    // --- CONSTRUÇÃO DA CONSULTA SQL DINÂMICA ---
    $where_clauses = [];
    $params = [];
    $types = "";

    $where_clauses[] = "(status_atual IS NULL OR LOWER(TRIM(status_atual)) NOT LIKE 'exclu%')";

    // 1. Aplica filtro de PERMISSÃO
    if ($usuario_logado['tipo'] === 'usuario_padrao') {
        $where_clauses[] = "id_usuario = ?";
        $params[] = $usuario_logado['id'];
        $types .= "i";
    } elseif ($usuario_logado['tipo'] === 'usuario_gerente') {
        $where_clauses[] = "equipe = ?";
        $params[] = $usuario_logado['equipe'];
        $types .= "s";
    }

    // 2. Aplica filtros da INTERFACE
    if (!$isOperador && $consultorFiltro) { $where_clauses[] = "nome_consultor = ?"; $params[] = $consultorFiltro; $types .= "s"; }
    if (!$isOperador && $equipeFiltro) { $where_clauses[] = "equipe = ?"; $params[] = $equipeFiltro; $types .= "s"; }
    if ($anoFiltro) { $where_clauses[] = "YEAR(data_venda) = ?"; $params[] = $anoFiltro; $types .= "i"; }
    if ($seguroFiltro) { $where_clauses[] = "seguro = ?"; $params[] = $seguroFiltro; $types .= "s"; }
    if ($tabelaFiltro) { $where_clauses[] = "tabela = ?"; $params[] = $tabelaFiltro; $types .= "s"; }
    
    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

    // --- CONSULTA 1: BUSCA OS DADOS DETALHADOS PARA O JAVASCRIPT ---
    $sql_data = "SELECT 
                    equipe, data_venda, nome_consultor AS consultor, numero_contrato AS contrato, 
                    parcelas_pagas_atual AS parcelas_pagas, valor_cota, status_atual AS situacao, 
                    tabela, seguro, qntd_contrato
                 FROM contratos 
                 {$where_sql}";
    
    $stmt_data = $conexao->prepare($sql_data);
    if (!empty($types)) {
        $stmt_data->bind_param($types, ...$params);
    }
    $stmt_data->execute();
    $resultado_data = $stmt_data->get_result();
    
    $dataset = [];
    while($linha = $resultado_data->fetch_assoc()) {
        // Formata a data para o padrão dd/mm/YYYY que o JavaScript espera
        if (!empty($linha['data_venda'])) {
            $linha['data_venda'] = (new DateTime($linha['data_venda']))->format('d/m/Y');
        }
        if ($isOperador) {
            $linha['consultor'] = 'Operador';
            $linha['equipe'] = '';
        }
        $dataset[] = $linha;
    }
    $stmt_data->close();

    // --- CONSULTA 2: CALCULA OS TOTAIS PARA OS CARDS ---
    $sql_totais = "SELECT 
                        SUM(qntd_contrato) as totalContratos,
                        SUM(valor_cota) as valorTotal,
                        SUM(CASE WHEN status_atual = 'Em Dia' THEN qntd_contrato ELSE 0 END) as contratosAtivos,
                        SUM(CASE WHEN seguro = 'Sim' THEN qntd_contrato ELSE 0 END) as totalComSeguro
                   FROM contratos
                   {$where_sql}";

    $stmt_totais = $conexao->prepare($sql_totais);
    if (!empty($types)) {
        $stmt_totais->bind_param($types, ...$params);
    }
    $stmt_totais->execute();
    $totais = $stmt_totais->get_result()->fetch_assoc();
    $stmt_totais->close();
    
    // Calcula a efetividade em PHP
    $totais['efetividade'] = ($totais['totalContratos'] > 0) ? (($totais['contratosAtivos'] / $totais['totalContratos']) * 100) : 0;

    // --- Envio da Resposta Final ---
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'meta' => ['totais' => $totais],
        'data' => $dataset
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Ocorreu um erro no servidor.', 'details' => $e->getMessage()]);
    exit;
}
?>
