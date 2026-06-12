<?php
// ARQUIVO: dados_clientes.php - VERSÃO FINAL LENDO DO BANCO DE DADOS

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

$isOperador = $usuario_eh_operador ?? ($usuario_logado['tipo'] === 'usuario_padrao');

// Ativar para debug. Em produção, comente ou mude para 0.
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // --- Captura dos Filtros da Interface ---
    $incluirInativos = $_GET['incluirInativos'] ?? 'false';
    $statusFiltro = $_GET['status'] ?? 'todos';
    $consultorFiltro = trim($_GET['consultor'] ?? '');
    $dataDe = trim($_GET['dataDe'] ?? '');
    $dataAte = trim($_GET['dataAte'] ?? '');
    $parcelaFiltro = trim($_GET['parcela'] ?? '');
    $busca = trim($_GET['busca'] ?? '');
    $clientId = trim($_GET['id'] ?? ''); // Para o modal de detalhes

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
    
    // 2. Aplica filtro de CONSULTORES INATIVOS
    if (!$isOperador && $incluirInativos === 'false') {
        $where_clauses[] = "(status_consultor IS NULL OR status_consultor != 'inativo')";
    }

    // 3. Aplica filtros da INTERFACE
    if ($statusFiltro !== 'todos') {
        if ($statusFiltro === 'em_dia') { $where_clauses[] = "status_atual = 'Em Dia'"; }
        elseif ($statusFiltro === 'atrasados') { $where_clauses[] = "status_atual LIKE '%Atraso%'"; }
        elseif ($statusFiltro === 'cancelados') { $where_clauses[] = "status_atual LIKE '%Cancelad%'"; }
    }
    if (!$isOperador && $consultorFiltro) { $where_clauses[] = "nome_consultor = ?"; $params[] = $consultorFiltro; $types .= "s"; }
    if ($dataDe) { $where_clauses[] = "data_venda >= ?"; $params[] = $dataDe; $types .= "s"; }
    if ($dataAte) { $where_clauses[] = "data_venda <= ?"; $params[] = $dataAte; $types .= "s"; }
    if ($parcelaFiltro) { $where_clauses[] = "parcelas_pagas_atual = ?"; $params[] = $parcelaFiltro; $types .= "i"; }
    if ($busca) {
        $where_clauses[] = "(nome_cliente LIKE ? OR numero_contrato LIKE ?)";
        $buscaParam = "%{$busca}%";
        $params[] = $buscaParam; $params[] = $buscaParam;
        $types .= "ss";
    }
     if ($clientId) { $where_clauses[] = "id = ?"; $params[] = $clientId; $types .= "i"; }


    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

    // --- CONSULTA 1: BUSCA OS DADOS PARA A TABELA E MODAL ---
    $sql = "SELECT * FROM contratos {$where_sql} ORDER BY data_venda DESC";
    
    $stmt = $conexao->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $dataset = [];
    while($linha = $resultado->fetch_assoc()) {
        if (!empty($linha['data_venda'])) {
            $linha['data_venda'] = (new DateTime($linha['data_venda']))->format('d/m/Y');
        }
        if ($isOperador) {
            unset(
                $linha['id_usuario'],
                $linha['nome_consultor'],
                $linha['email_consultor'],
                $linha['status_consultor'],
                $linha['equipe']
            );
        }
        $dataset[] = $linha;
    }
    $stmt->close();
    
    // Se a requisição foi por um ID específico (modal), retorna apenas esse dado
    if ($clientId) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $dataset[0] ?? null]);
        exit;
    }

    // --- CONSULTA 2: CALCULA OS TOTAIS PARA OS CARDS ---
    // Usamos a mesma condição WHERE para garantir que os totais batam com os dados filtrados
    $sql_totais = "SELECT 
                        COUNT(*) as totalContratos,
                        SUM(CASE WHEN status_atual = 'Em Dia' THEN 1 ELSE 0 END) as emDia,
                        SUM(CASE WHEN status_atual LIKE '%Atraso%' THEN 1 ELSE 0 END) as atrasados,
                        SUM(CASE WHEN status_atual LIKE '%Cancelad%' THEN 1 ELSE 0 END) as cancelados,
                        SUM(valor_cota) as valorTotalVendas,
                        COUNT(CASE WHEN parcelas_pagas_atual >= 1 THEN 1 END) as totalEfetividadeBase,
                        COUNT(CASE WHEN parcelas_pagas_atual >= 1 AND status_atual = 'Em Dia' THEN 1 END) as pagoEfetividadeBase
                   FROM contratos
                   {$where_sql}";

    $stmt_totais = $conexao->prepare($sql_totais);
    if (!empty($types)) {
        $stmt_totais->bind_param($types, ...$params);
    }
    $stmt_totais->execute();
    $totais_bruto = $stmt_totais->get_result()->fetch_assoc();
    $stmt_totais->close();
    
    $totais = $totais_bruto;
    $totais['efetividade'] = ($totais['totalEfetividadeBase'] ?? 0) > 0 ? (($totais['pagoEfetividadeBase'] ?? 0) / $totais['totalEfetividadeBase']) * 100 : 0;

    // --- Finaliza e envia a resposta ---
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'meta' => ['totais' => $totais ],
        'data' => $dataset
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar a solicitação: ' . $e->getMessage()]);
    exit;
}
?>
