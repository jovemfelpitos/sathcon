<?php
// ARQUIVO: dados_ranking.php (A Cozinha) - VERSÃO FINAL LENDO DO BANCO DE DADOS

if (!isset($permissoes_necessarias)) {
    $permissoes_necessarias = ['admin', 'usuario_gestor', 'usuario_gerente'];
}

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- FUNÇÕES AUXILIARES ---
function brl($n){ return 'R$ '.number_format((float)$n, 2, ',', '.'); }
function normalizeName($str){ $str = trim($str); $str = mb_strtolower($str, 'UTF-8'); $str = str_replace( ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'], ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'], $str ); return $str; }

// --- PERÍODO ---
$dataDe  = !empty($_GET['dataDe']) ? DateTime::createFromFormat('Y-m-d', $_GET['dataDe']) : null;
$dataAte = !empty($_GET['dataAte']) ? DateTime::createFromFormat('Y-m-d', $_GET['dataAte']) : null;
if (!$dataDe || !$dataAte) {
    $hoje = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $dataDe  = (clone $hoje)->modify('first day of this month')->setTime(0,0,0);
    $dataAte = (clone $hoje)->modify('last day of this month')->setTime(23,59,59);
} else {
    $dataDe->setTime(0,0,0);
    $dataAte->setTime(23,59,59);
}
$filterEquipe = $_GET['equipe'] ?? '';

// --- INICIALIZAÇÃO DE VARIÁVEIS ---
$rank = []; $top3 = []; $labels = []; $dados = []; $counts = []; $vizOrder = [1,0,2];
$fotosConsultoresNorm = []; $equipesList = []; $gsError = '';

try {
    // --- BUSCA FOTOS E LISTA DE EQUIPES ---
    $res_fotos = $conexao->query("SELECT nome, url_foto_perfil FROM usuarios");
    if ($res_fotos) {
        while($row = $res_fotos->fetch_assoc()){
            if (!empty($row['url_foto_perfil'])) {
                $fotosConsultoresNorm[normalizeName($row['nome'])] = $row['url_foto_perfil'];
            }
        }
    }
    
    $res_equipes = $conexao->query("SELECT DISTINCT equipe FROM contratos WHERE equipe IS NOT NULL AND equipe != '' AND (status_atual IS NULL OR LOWER(TRIM(status_atual)) NOT LIKE 'exclu%') ORDER BY equipe ASC");
    if ($res_equipes) {
        while ($linha = $res_equipes->fetch_assoc()) {
            $equipesList[] = $linha['equipe'];
        }
    }

    // --- CÁLCULO DO RANKING COM CONSULTA SQL OTIMIZADA ---
    $where_clauses = ["data_venda BETWEEN ? AND ?", "(status_atual IS NULL OR LOWER(TRIM(status_atual)) NOT LIKE 'exclu%')"];
    $params = [$dataDe->format('Y-m-d'), $dataAte->format('Y-m-d')];
    $types = "ss";

    if ($filterEquipe) {
        $where_clauses[] = "equipe = ?";
        $params[] = $filterEquipe;
        $types .= "s";
    }

    // A consulta já agrupa, soma, ordena e limita, fazendo todo o trabalho pesado!
    $sql = "SELECT nome_consultor, SUM(valor_cota) as total, COUNT(id) as 'count'
            FROM contratos
            WHERE " . implode(' AND ', $where_clauses) . "
            GROUP BY nome_consultor
            ORDER BY total DESC";
            
    $stmt = $conexao->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    while($linha = $resultado->fetch_assoc()) {
        $rank[$linha['nome_consultor']] = [
            'total' => (float)$linha['total'],
            'count' => (int)$linha['count']
        ];
    }
    $stmt->close();
    
    // Pega o Top 3 do resultado já ordenado
    $top3 = array_slice($rank, 0, 3, true);

} catch (Exception $e) {
    $gsError = "Erro ao processar dados: " . $e->getMessage();
}

// Garante que a página não quebre se não houver resultados
while (count($top3) < 3) {
    $top3['Vaga '. (count($top3) + 1)] = ['total'=>0.0,'count'=>0];
}
$labels = array_keys($top3);
$dados  = array_column($top3,'total');
$counts = array_column($top3,'count');

// Fim da Cozinha. Todas as variáveis estão prontas.
?>
