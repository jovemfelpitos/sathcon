<?php
// meu_perfil.php (O Salão)
require_once __DIR__ . '/includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container py-4">
        <h3 class="fw-bold mb-4">Meu Perfil</h3>

        <?php // --- MENSAGENS DE FEEDBACK PARA TROCA DE SENHA ---
        if (isset($_GET['status_senha'])):
            $status = $_GET['status_senha'];
            $mensagem = '';
            $tipo_alerta = 'danger';

            if ($status === 'sucesso') {
                $mensagem = 'Senha alterada com sucesso!';
                $tipo_alerta = 'success';
            } elseif ($status === 'erro_nao_confere') {
                $mensagem = 'Erro: A nova senha e a confirmação não são iguais.';
            } elseif ($status === 'erro_senha_atual') {
                $mensagem = 'Erro: A sua senha atual está incorreta.';
            } elseif ($status === 'erro_curta') {
                $mensagem = 'Erro: A nova senha deve ter no mínimo 8 caracteres.';
            } else {
                $mensagem = 'Ocorreu um erro. Tente novamente.';
            }
        ?>
            <div class="alert alert-<?= $tipo_alerta ?>"><?= $mensagem ?></div>
        <?php endif; ?>

        <div class="card p-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-md-3 text-center">
                    <p class="fw-bold">Foto Atual</p>
                    <img src="<?= htmlspecialchars($usuario_logado['url_foto'] ?? 'assets/img/default.png') ?>" alt="Foto de Perfil" class="img-fluid rounded-circle mb-2" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--border-color);">
                </div>
                <div class="col-md-9">
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'sucesso'): ?>
                        <div class="alert alert-success">Foto atualizada com sucesso!</div>
                    <?php endif; ?>
                    <form action="upload_foto.php" method="post" enctype="multipart/form-data">
                        <h4 class="mb-3">Alterar Foto de Perfil</h4>
                        <div class="mb-3">
                            <label for="foto" class="form-label">Escolha uma nova foto (JPG, PNG - Máx 2MB):</label>
                            <input class="form-control" type="file" name="foto" id="foto" accept="image/jpeg, image/png" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Salvar Nova Foto</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <h4 class="mb-3">Alterar Senha</h4>
            <form action="alterar_senha.php" method="post">
                <div class="mb-3">
                    <label for="senha_atual" class="form-label">Senha Atual</label>
                    <input type="password" class="form-control" name="senha_atual" id="senha_atual" required>
                </div>
                <div class="mb-3">
                    <label for="nova_senha" class="form-label">Nova Senha (mínimo 8 caracteres)</label>
                    <input type="password" class="form-control" name="nova_senha" id="nova_senha" required minlength="8">
                </div>
                <div class="mb-3">
                    <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" name="confirma_senha" id="confirma_senha" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-key-fill"></i> Alterar Senha
                </button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>