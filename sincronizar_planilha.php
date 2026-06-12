<?php
// ARQUIVO: sincronizar_planilha.php (O Robô Sincronizador) - VERSÃO COM CORREÇÃO DEFINITIVA NO UPDATE

// Configurações, 'use' statements, etc.
ini_set('display_errors', 1); error_reporting(E_ALL);
set_time_limit(600);
ignore_user_abort(true);
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;

// Bloco de Segurança
// Bloco de Segurança (Atualizado para V2)
$CHAVE_SECRETA_CRON = 'SuaChaveSuperSecreta12345!@#ParaNinguemAdivinhar';
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) { session_start(); }

// Puxa o nível do usuário seja pela sessão V2 ou pela legada V1
$tipo_usuario_atual = $_SESSION['usuario_logado']['tipo'] ?? ($_SESSION['tipo_usuario'] ?? '');

// Libera se for admin, gestor ou gerente
$is_gestor_logado = in_array($tipo_usuario_atual, ['admin', 'usuario_gestor', 'usuario_gerente']);
$is_cron_job = (isset($_GET['secret']) && $_GET['secret'] === $CHAVE_SECRETA_CRON);
$is_via_cli = (php_sapi_name() === 'cli');

// Verifica se é o botão do painel chamando
$is_ajax = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST');

if (!$is_gestor_logado && !$is_cron_job && !$is_via_cli) { 
    http_response_code(403); 
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso Negado: Você não tem permissão para rodar o robô.']);
        exit;
    }
    die('<h1>Acesso Negado</h1>'); 
}

if ($is_ajax) {
    header('Content-Type: application/json');
    ob_start(); 
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/vendor/autoload.php';

// --- ADICIONE ISTO AQUI ---
// Verifica se a requisição veio do botão no Dashboard (AJAX/POST)
$is_ajax = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST');

if ($is_ajax) {
    header('Content-Type: application/json');
    ob_start(); // Liga o "silenciador" de HTML. Tudo que for dado 'echo' fica preso na memória.
}
// -------------------------

echo "<h1>Iniciando Sincronização com a Planilha...</h1>";

echo "<h1>Iniciando Sincronização com a Planilha...</h1>";
echo "<p>Início: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Por favor, não feche esta janela. O processo pode demorar...</p><hr>";
if (!isset($is_ajax) || !$is_ajax) { ob_flush(); flush(); }

// --- FUNÇÕES AUXILIARES ---
function registrarLog($id_contrato, $campo, $valor_antigo, $valor_novo) {
    global $conexao;
    if ($valor_antigo == $valor_novo) return;
    $stmt = $conexao->prepare("INSERT INTO contratos_log_alteracoes (id_contrato, campo_alterado, valor_antigo, valor_novo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $id_contrato, $campo, $valor_antigo, $valor_novo);
    $stmt->execute();
    $stmt->close();
}
function parseCurrency($v) { if (empty($v)) return 0.0; $v = str_replace(['R$', ' '], '', $v); $v = str_replace('.', '', $v); $v = str_replace(',', '.', $v); return floatval($v); }
function parseDate($s) { if (empty(trim($s))) return null; $dt = DateTime::createFromFormat('d/m/Y', trim($s)); return $dt ? $dt->format('Y-m-d') : null; }

try {
    // --- 1. PRÉ-BUSCAS NO BANCO DE DADOS ---
    $usuarios_map = [];
    $res_usuarios = $conexao->query("SELECT id, email FROM usuarios");
    while($row = $res_usuarios->fetch_assoc()) { $usuarios_map[strtolower(trim($row['email']))] = $row['id']; }
    echo "<p>OK: " . count($usuarios_map) . " usuÃ¡rios carregados para vincular contratos.</p><hr>";
    if (!isset($is_ajax) || !$is_ajax) { ob_flush(); flush(); }

    // --- 2. CONEXÃO COM GOOGLE SHEETS ---
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/credenciais/consorciosath-b1d72523a215.json');
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
    $service = new Google_Service_Sheets($client);
    $spreadsheetId = '1X292BqBRY-7kMpPR0ucuPfCefnhFa8Z2uAPKGfFz44A';
    $range = "'efetividade'!A1:U10000";
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues() ?: [];
    if (empty($values)) throw new Exception("Nenhum dado encontrado na planilha.");
    echo "<p style='color:green;'>OK: Planilha lida com sucesso. " . (count($values) - 1) . " linhas de dados encontradas.</p><hr>";
    if (!isset($is_ajax) || !$is_ajax) { ob_flush(); flush(); }
    
    $header = array_shift($values);
    $rows = $values;
    $map = [
        'equipe' => 1, 'data_venda' => 2, 'consultor' => 3, 'qntd_contrato' => 4,
        'contrato' => 5, 'grupo_contrato' => 6, 'parcelas_pagas' => 7, 'valor_cota' => 8,
        'tabela' => 9, 'telefone' => 10, 'cliente' => 11, 'data_vencimento' => 12, 
        'data_pagamento' => 13, 'seguro' => 14, 'situacao' => 15,
        'data_conclusao' => 16, 'comissao' => 17, 'email_consultor' => 18, 
        'status_consultor' => 19, 'email_cliente' => 20
    ];

    // --- 3. LOOP PRINCIPAL DE SINCRONIZAÇÃO ---
    $contratos_processados = 0; $contratos_novos = 0; $contratos_removidos = 0; $alteracoes_registradas = 0;

    $stmt_insert = $conexao->prepare("INSERT INTO contratos (numero_contrato, id_usuario, nome_consultor, email_consultor, status_consultor, equipe, data_venda, qntd_contrato, grupo_contrato, parcelas_pagas_atual, valor_cota, tabela, nome_cliente, telefone_cliente, email_cliente, data_vencimento, data_pagamento, seguro, status_atual, data_conclusao, comissao_planilha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $conexao->begin_transaction();
    $conexao->query("DELETE FROM contratos");
    $contratos_removidos = $conexao->affected_rows;
    $contratos_no_db = [];
    echo "<p>OK: {$contratos_removidos} contratos antigos removidos do banco antes da recarga.</p><hr>";
    if (!isset($is_ajax) || !$is_ajax) { ob_flush(); flush(); }

    foreach($rows as $r) {
        $numero_contrato = trim($r[$map['contrato']] ?? '');
        if (empty($numero_contrato)) continue;

        $email_consultor = strtolower(trim($r[$map['email_consultor']] ?? ''));
        $id_usuario = $usuarios_map[$email_consultor] ?? null;
        
        $dados_planilha = [
            'numero_contrato' => $numero_contrato, 'id_usuario' => $id_usuario,
            'nome_consultor' => trim($r[$map['consultor']] ?? ''), 'email_consultor' => $email_consultor,
            'status_consultor' => trim($r[$map['status_consultor']] ?? ''), 'equipe' => trim($r[$map['equipe']] ?? ''),
            'data_venda' => parseDate($r[$map['data_venda']] ?? ''), 'qntd_contrato' => intval($r[$map['qntd_contrato']] ?? 1),
            'grupo_contrato' => trim($r[$map['grupo_contrato']] ?? ''), 'parcelas_pagas_atual' => intval($r[$map['parcelas_pagas']] ?? 0),
            'valor_cota' => parseCurrency($r[$map['valor_cota']] ?? 0), 'tabela' => trim($r[$map['tabela']] ?? ''),
            'nome_cliente' => trim($r[$map['cliente']] ?? ''), 'telefone_cliente' => trim($r[$map['telefone']] ?? ''),
            'email_cliente' => trim($r[$map['email_cliente']] ?? ''), 'data_vencimento' => parseDate($r[$map['data_vencimento']] ?? ''),
            'data_pagamento' => parseDate($r[$map['data_pagamento']] ?? ''), 'seguro' => trim($r[$map['seguro']] ?? ''),
            'status_atual' => trim($r[$map['situacao']] ?? ''), 'data_conclusao' => parseDate($r[$map['data_conclusao']] ?? ''),
            'comissao_planilha' => parseCurrency($r[$map['comissao']] ?? 0)
        ];

        if (!isset($contratos_no_db[$numero_contrato])) {
            $stmt_insert->bind_param("sisssssisidsssssssssd", $dados_planilha['numero_contrato'], $dados_planilha['id_usuario'], $dados_planilha['nome_consultor'], $dados_planilha['email_consultor'], $dados_planilha['status_consultor'], $dados_planilha['equipe'], $dados_planilha['data_venda'], $dados_planilha['qntd_contrato'], $dados_planilha['grupo_contrato'], $dados_planilha['parcelas_pagas_atual'], $dados_planilha['valor_cota'], $dados_planilha['tabela'], $dados_planilha['nome_cliente'], $dados_planilha['telefone_cliente'], $dados_planilha['email_cliente'], $dados_planilha['data_vencimento'], $dados_planilha['data_pagamento'], $dados_planilha['seguro'], $dados_planilha['status_atual'], $dados_planilha['data_conclusao'], $dados_planilha['comissao_planilha']);
            if (!$stmt_insert->execute()) {
                throw new Exception("Falha ao inserir contrato {$numero_contrato}: " . $stmt_insert->error);
            }
            $novo_id_contrato = $conexao->insert_id;
            if ($novo_id_contrato > 0) {
                echo "<p style='color:blue;'>NOVO CONTRATO INSERIDO: {$numero_contrato}</p>";
                $contratos_novos++;
            }
        } else {
            $contrato_db = $contratos_no_db[$numero_contrato];
            $mudancas = [];
            $campos_para_verificar = ['id_usuario', 'status_atual', 'parcelas_pagas_atual', 'equipe', 'nome_consultor'];
            foreach($campos_para_verificar as $campo) {
                if ($dados_planilha[$campo] != $contrato_db[$campo]) {
                    $mudancas[$campo] = ['antigo' => $contrato_db[$campo], 'novo' => $dados_planilha[$campo]];
                }
            }

            if (!empty($mudancas)) {
                echo "<p>MUDANÇA(S) DETECTADA(S) no contrato {$numero_contrato}:</p><ul>";
                foreach ($mudancas as $campo => $valores) {
                    registrarLog($contrato_db['id'], $campo, $valores['antigo'], $valores['novo']);
                    echo "<li>Campo '{$campo}' mudou. Log registrado.</li>";
                    $alteracoes_registradas++;
                }
                echo "</ul>";
                
                $id_do_contrato_db = $contrato_db['id'];
                
                // ===================================================================
                // CORREÇÃO DEFINITIVA DO BIND_PARAM DO UPDATE
                // ===================================================================
                $stmt_update->bind_param(
                    "isssssisidsssssssssdi", // 21 tipos: i, s(5), i, s, i, d, s(9), d, i
                    $dados_planilha['id_usuario'], $dados_planilha['nome_consultor'], $dados_planilha['email_consultor'], 
                    $dados_planilha['status_consultor'], $dados_planilha['equipe'], $dados_planilha['data_venda'], 
                    $dados_planilha['qntd_contrato'], $dados_planilha['grupo_contrato'], $dados_planilha['parcelas_pagas_atual'], 
                    $dados_planilha['valor_cota'], $dados_planilha['tabela'], $dados_planilha['nome_cliente'], 
                    $dados_planilha['telefone_cliente'], $dados_planilha['email_cliente'], $dados_planilha['data_vencimento'], 
                    $dados_planilha['data_pagamento'], $dados_planilha['seguro'], $dados_planilha['status_atual'], 
                    $dados_planilha['data_conclusao'], $dados_planilha['comissao_planilha'], 
                    $id_do_contrato_db
                );
                
                if($stmt_update->execute()) {
                    echo "<p style='color:green;'><b>SUCESSO:</b> Tabela 'contratos' atualizada para o contrato {$numero_contrato}.</p>";
                } else {
                    echo "<p style='color:red;'><b>FALHA NO UPDATE:</b> " . htmlspecialchars($stmt_update->error) . "</p>";
                }
            }
        }
        $contratos_processados++;
    }

    $stmt_insert->close();
    $conexao->commit();
    
    echo "<hr><h2>Sincronização Concluída!</h2>";
    echo "<p>Total de linhas da planilha analisadas: {$contratos_processados}</p>";
    echo "<p>Contratos antigos removidos: {$contratos_removidos}</p>";
    echo "<p>Contratos recarregados da planilha: {$contratos_novos}</p>";
    echo "<p>Total de alterações registradas no log: {$alteracoes_registradas}</p>";
    echo "<p>Fim: " . date('Y-m-d H:i:s') . "</p>";
    
    // --- ADICIONE ISTO AQUI (SUCESSO) ---
    if ($is_ajax) {
        $html_escondido = ob_get_clean(); // Limpa todo o HTML da memória
        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Recarga completa | Removidos: $contratos_removidos | Recarregados: $contratos_novos"
        ]);
        exit;
    }
    // ------------------------------------

} catch (Throwable $t) {
    if (isset($conexao) && $conexao instanceof mysqli) {
        @$conexao->rollback();
    }
    // --- ADICIONE ISTO AQUI (ERRO) ---
    if (isset($is_ajax) && $is_ajax) {
        ob_end_clean(); // Limpa o HTML da memória
        echo json_encode([
            'sucesso' => false,
            'mensagem' => "Erro no PHP: " . $t->getMessage()
        ]);
        exit;
    }
    // ---------------------------------

} catch (Throwable $t) {
    echo "<h1>ERRO FATAL DURANTE A SINCRONIZAÇÃO</h1>";
    echo "<pre style='background-color:#333; color: #ff9999; padding:10px; border-radius:5px; white-space: pre-wrap; word-wrap: break-word;'>";
    echo "<strong>Mensagem:</strong> " . $t->getMessage() . "\n";
    echo "<strong>Arquivo:</strong> " . $t->getFile() . "\n";
    echo "<strong>Linha:</strong> " . $t->getLine();
    echo "</pre>";
}
?>
