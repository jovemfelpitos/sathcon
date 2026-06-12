<?php
// ARQUIVO: processar_comissoes.php (O Robô Contador) - VERSÃO FINAL CORRIGIDA

// Configurações de erro e tempo de execução
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // Aumenta o tempo limite de execução para 5 minutos

// As declarações 'use' devem estar no topo do escopo global
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

echo "<h1>Iniciando Processamento de Comissões...</h1>";
ob_flush();
flush();

// Bloco Try/Catch aprimorado para capturar qualquer tipo de erro
try {
    // --- Funções Auxiliares ---
    function parseCurrency($v) { if (empty($v)) return 0.0; $v = preg_replace('/[^\d\.,]/', '', $v); if (strpos($v, ',') !== false) { $v = str_replace('.', '', $v); $v = str_replace(',', '.', $v); } return floatval($v); }
    function parseDateGuess($s) { $s = trim((string)$s); if (!$s) return false; $formats = ['d/m/Y','Y-m-d']; foreach ($formats as $f) { $dt = DateTime::createFromFormat($f, $s); if ($dt !== false) { $dt->setTime(0,0,0); return $dt;} } return false; }

    // --- Tabelas de Comissão ---
    $comissoes_leve = [ 1 => 0.0022, 2 => 0.0012, 3 => 0.0012, 4 => 0.0012, 5 => 0.0011, 6 => 0.0011 ];
    $comissoes_pesado = [ 1 => 0.0012, 2 => 0.0008, 3 => 0.0010, 4 => 0.0012, 5 => 0.0012, 6 => 0.0014 ];

    // --- 1. Carregar dados da Planilha ---
    $client = new Google_Client();
    $client->setApplicationName('Robo de Comissoes');
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
    $caminho_credenciais = __DIR__ . '/credenciais/consorciosath-b1d72523a215.json';
    if (!file_exists($caminho_credenciais)) {
        throw new Exception("ARQUIVO DE CREDENCIAIS NÃO ENCONTRADO EM: " . $caminho_credenciais);
    }
    $client->setAuthConfig($caminho_credenciais);
    $service = new Google_Service_Sheets($client);
    $spreadsheetId = '1X292BqBRY-7kMpPR0ucuPfCefnhFa8Z2uAPKGfFz44A';
    $range = "'efetividade'!A1:S10000";
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues() ?: [];
    
    if (empty($values)) {
        throw new Exception("Nenhum dado encontrado na planilha para processar.");
    }
    
    $rows = array_slice($values, 1);
    $indices = [ 'consultor_email' => 18, 'contrato' => 5, 'parcelas_pagas' => 7, 'valor_cota' => 8, 'tabela' => 9, 'data_venda' => 2, 'situacao' => 15 ];

    // --- 2. Buscar todos os usuários do banco ---
    $usuarios_map = [];
    $resultado_usuarios = $conexao->query("SELECT id, email FROM usuarios");
    while($linha = $resultado_usuarios->fetch_assoc()) {
        $usuarios_map[strtolower(trim($linha['email']))] = $linha['id'];
    }

    // --- 3. Buscar o último registro de cada contrato no "livro-caixa" ---
    $ultimas_parcelas_registradas = [];
    $resultado_historico = $conexao->query("SELECT numero_contrato, MAX(parcela_numero) as ultima_parcela FROM historico_comissoes GROUP BY numero_contrato");
    while($linha = $resultado_historico->fetch_assoc()) {
        $ultimas_parcelas_registradas[$linha['numero_contrato']] = $linha['ultima_parcela'];
    }

    // --- 4. Processar cada linha da planilha ---
    echo "<hr>";
    $lancamentos_feitos = 0;
    foreach($rows as $r) {
        $contrato = trim($r[$indices['contrato']] ?? '');
        $email = strtolower(trim($r[$indices['consultor_email']] ?? ''));
        $parcelas_pagas_planilha = intval($r[$indices['parcelas_pagas']] ?? 0);

        if (empty($contrato) || empty($email) || $parcelas_pagas_planilha == 0) continue;

        $id_usuario = $usuarios_map[$email] ?? null;
        if (!$id_usuario) continue;

        $ultima_registrada_db = $ultimas_parcelas_registradas[$contrato] ?? 0;

        if ($parcelas_pagas_planilha > $ultima_registrada_db) {
            $valorCota = parseCurrency($r[$indices['valor_cota']] ?? '0');
            $tabela = trim($r[$indices['tabela']] ?? '');
            $dataVenda = parseDateGuess($r[$indices['data_venda']] ?? '');
            $situacao = trim($r[$indices['situacao'] ?? '']);

            if (!$dataVenda || $valorCota <= 0 || empty($tabela)) continue;

            $tabelaComissao = (strcasecmp($tabela, 'Leve') === 0) ? $comissoes_leve : $comissoes_pesado;

            for ($p = $ultima_registrada_db + 1; $p <= $parcelas_pagas_planilha; $p++) {
                if ($p > 6) break;

                $dataPagamento = (clone $dataVenda)->modify('+' . ($p - 1) . ' months');
                $comissaoDaParcela = $valorCota * ($tabelaComissao[$p] ?? 0);

                $stmt = $conexao->prepare(
                    "INSERT INTO historico_comissoes (id_usuario, numero_contrato, parcela_numero, valor_comissao, data_pagamento_parcela, status_no_pagamento) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $dataPagamentoStr = $dataPagamento->format('Y-m-d');
                $stmt->bind_param("isidss", $id_usuario, $contrato, $p, $comissaoDaParcela, $dataPagamentoStr, $situacao);
                
                if ($stmt->execute()) {
                    echo "<p style='color:green;'>SUCESSO: Lançada comissão da parcela #{$p} para o contrato {$contrato}.</p>";
                    $lancamentos_feitos++;
                } else {
                    echo "<p style='color:red;'>ERRO: Falha ao lançar parcela #{$p} para o contrato {$contrato}. Motivo: " . $stmt->error . "</p>";
                }
                ob_flush();
                flush();
            }
        }
    }

    echo "<hr><h2>Processamento Concluído!</h2>";
    echo "<p>Total de novos lançamentos de comissão: <strong>{$lancamentos_feitos}</strong></p>";

} catch (Throwable $t) { // Captura qualquer tipo de erro
    echo "<hr>";
    echo "<h1>❌ ERRO FATAL CAPTURADO ❌</h1>";
    echo "<h3>A causa do erro 500 é esta:</h3>";
    echo "<pre style='background-color:#333; color: #ff9999; padding:10px; border-radius:5px; white-space: pre-wrap; word-wrap: break-word;'>";
    echo "<strong>Tipo de Erro:</strong> " . get_class($t) . "\n";
    echo "<strong>Mensagem:</strong> " . $t->getMessage() . "\n\n";
    echo "<strong>Arquivo:</strong> " . $t->getFile() . "\n";
    echo "<strong>Linha:</strong> " . $t->getLine();
    echo "</pre>";
}

?>