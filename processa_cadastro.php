<?php
// ARQUIVO: processa_cadastro.php (A Cozinha do Cadastro)

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

// Verificação dupla: apenas admins podem executar esta ação.
if ($usuario_logado['tipo'] !== 'admin') {
    http_response_code(403);
    die("Acesso negado.");
}

// 1. Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 2. Pega e limpa os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $equipe = empty($_POST['equipe']) ? null : trim($_POST['equipe']); // Define como NULL se vier vazio

    // 3. Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        header("Location: criar_usuario.php?status=erro_campos_vazios");
        exit;
    }

    // 4. Verifica se o e-mail já existe
    $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        header("Location: criar_usuario.php?status=erro_email_existe");
        exit;
    }
    $stmt->close();

    // 5. Cria o HASH da senha (NUNCA SALVE A SENHA EM TEXTO PLANO)
    $hash_senha = password_hash($senha, PASSWORD_DEFAULT);

    // 6. Insere o novo usuário no banco de dados
    $stmt_insert = $conexao->prepare(
        "INSERT INTO usuarios (nome, email, senha, tipo_usuario, equipe) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt_insert->bind_param("sssss", $nome, $email, $hash_senha, $tipo_usuario, $equipe);
    
    if ($stmt_insert->execute()) {
        // Tudo certo! Redireciona com sucesso.
        header("Location: criar_usuario.php?status=sucesso");
        exit;
    } else {
        // Erro ao salvar no banco
        header("Location: criar_usuario.php?status=erro_banco");
        exit;
    }

} else {
    // Se alguém tentar acessar este arquivo diretamente, redireciona
    header('Location: criar_usuario.php');
    exit;
}
?>