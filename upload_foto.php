<?php
// ARQUIVO: upload_foto.php (A Cozinha do Upload)

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

// Verifica se um arquivo foi enviado e se não houve erro
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    
    $arquivo_tmp = $_FILES['foto']['tmp_name'];
    $nome_original = $_FILES['foto']['name'];
    $tamanho = $_FILES['foto']['size'];
    $tipo = $_FILES['foto']['type'];

    // 1. Validações de Segurança
    $tipos_permitidos = ['image/jpeg', 'image/png'];
    $tamanho_maximo = 2 * 1024 * 1024; // 2 MB

    if (!in_array($tipo, $tipos_permitidos)) {
        header("Location: meu_perfil.php?status=erro"); // Redireciona com erro
        exit;
    }
    if ($tamanho > $tamanho_maximo) {
        header("Location: meu_perfil.php?status=erro"); // Redireciona com erro
        exit;
    }

    // 2. Cria um nome de arquivo único e seguro
    $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    $novo_nome = "user_" . $usuario_logado['id'] . "_" . time() . "." . $extensao;

    // 3. Define o caminho final e move o arquivo
    $caminho_destino = "uploads/fotos_perfil/" . $novo_nome;
    
    if (move_uploaded_file($arquivo_tmp, $caminho_destino)) {
        // 4. Se o upload deu certo, atualiza o banco de dados
        $id_usuario = $usuario_logado['id'];
        
        // (Opcional) Deletar a foto antiga para economizar espaço
        // $sql_foto_antiga = "SELECT url_foto_perfil FROM usuarios WHERE id = ?";
        // ... (lógica para pegar o caminho antigo e usar unlink($caminho_antigo))

        $stmt = $conexao->prepare("UPDATE usuarios SET url_foto_perfil = ? WHERE id = ?");
        $stmt->bind_param("si", $caminho_destino, $id_usuario);
        
        if ($stmt->execute()) {
            // Sucesso! Atualiza a sessão e redireciona.
            $_SESSION['url_foto_perfil'] = $caminho_destino;
            header("Location: meu_perfil.php?status=sucesso");
            exit;
        } else {
            // Se falhou ao atualizar o banco, deleta a foto que acabamos de subir
            unlink($caminho_destino);
            header("Location: meu_perfil.php?status=erro");
            exit;
        }

    } else {
        header("Location: meu_perfil.php?status=erro");
        exit;
    }

} else {
    header("Location: meu_perfil.php?status=erro");
    exit;
}
?>