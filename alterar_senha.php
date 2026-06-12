<?php
// ARQUIVO: alterar_senha.php (A Cozinha da Troca de Senha)

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

// 1. Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 2. Pega os dados do formulário
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $id_usuario = $usuario_logado['id'];

    // 3. Validações iniciais
    if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
        header("Location: meu_perfil.php?status_senha=erro_vazio");
        exit;
    }

    if ($nova_senha !== $confirma_senha) {
        header("Location: meu_perfil.php?status_senha=erro_nao_confere");
        exit;
    }
    
    if (strlen($nova_senha) < 8) {
        header("Location: meu_perfil.php?status_senha=erro_curta");
        exit;
    }

    // 4. Verifica se a "Senha Atual" está correta
    $stmt = $conexao->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $hash_do_banco = $usuario['senha'];

    if (!password_verify($senha_atual, $hash_do_banco)) {
        // A senha atual digitada não bate com a do banco de dados
        header("Location: meu_perfil.php?status_senha=erro_senha_atual");
        exit;
    }

    // 5. Se a senha atual estiver correta, gera o hash da NOVA senha
    $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // 6. Atualiza a nova senha no banco de dados
    $stmt_update = $conexao->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt_update->bind_param("si", $novo_hash, $id_usuario);
    
    if ($stmt_update->execute()) {
        // Tudo certo! Redireciona com sucesso.
        header("Location: meu_perfil.php?status_senha=sucesso");
        exit;
    } else {
        // Erro ao salvar no banco
        header("Location: meu_perfil.php?status_senha=erro_banco");
        exit;
    }

} else {
    // Se alguém tentar acessar este arquivo diretamente, redireciona
    header('Location: meu_perfil.php');
    exit;
}
?>