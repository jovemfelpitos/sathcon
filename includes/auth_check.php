<?php
// includes/auth_check.php

// 1. Inicia a sessão (se já não tiver sido iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. VERIFICAÇÃO BÁSICA: O usuário está logado?
// Se não tiver e-mail e tipo na sessão, é expulso para a página de login.
if (!isset($_SESSION['email'], $_SESSION['tipo_usuario'])) {
    header('Location: index.php');
    exit;
}

// ----------------------------------------------------
// NOVA FUNÇÃO PARA TRADUZIR OS TIPOS DE USUÁRIO
// ----------------------------------------------------
function traduzirTipoUsuario($tipo) {
    $mapa_tipos = [
        'admin'           => 'Mestre',
        'usuario_gestor'  => 'Gestor',
        'usuario_gerente' => 'Gerente',
        'usuario_padrao'  => 'Operador'
    ];

    // Retorna o nome bonito se encontrar no mapa, ou o nome original com a primeira letra maiúscula
    return $mapa_tipos[$tipo] ?? ucfirst($tipo);
}


// 3. COLETA OS DADOS DO USUÁRIO LOGADO EM UM SÓ LUGAR (AGORA COM O NOME BONITO)
// Isso facilita o uso nas outras páginas.
$usuario_logado = [
    'id'        => $_SESSION['id'],
    'nome'      => $_SESSION['nome'],
    'email'     => $_SESSION['email'],
    'tipo'      => $_SESSION['tipo_usuario'], // O tipo original, para a lógica de permissão
    'tipo_bonito' => traduzirTipoUsuario($_SESSION['tipo_usuario']), // O nome traduzido, para exibição
    'equipe'    => $_SESSION['equipe'] ?? null,
    'url_foto'  => $_SESSION['url_foto_perfil'] ?? null
];

$usuario_eh_operador = $usuario_logado['tipo'] === 'usuario_padrao';

// 4. VERIFICAÇÃO DE PERMISSÃO ESPECÍFICA DA PÁGINA (Acesso VIP)
// A página que incluir este arquivo pode definir um array $permissoes_necessarias
if (isset($permissoes_necessarias) && !in_array($usuario_logado['tipo'], $permissoes_necessarias)) {
    // Se o tipo do usuário não está na lista de permissões, ele é barrado.
    http_response_code(403); // Código "Forbidden"
    echo "<h1>Acesso Negado</h1><p>Você não tem permissão para acessar esta página.</p>";
    exit;
}
?>
