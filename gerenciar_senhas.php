<?php
// ARQUIVO: gerenciar_senhas.php (VERSÃO UNIFICADA: "COZINHA" + "SALÃO")

// Acesso restrito! Apenas usuários do tipo 'admin' podem ver esta página.
$permissoes_necessarias = ['admin'];
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

// Inicia as variáveis de feedback
$mensagem = '';
$tipo_alerta = '';

// --- BLOCO DE PROCESSAMENTO DO FORMULÁRIO (A "COZINHA") ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (empty($usuario_id) || empty($action)) {
        $mensagem = "Seleção inválida. Por favor, escolha um usuário e uma ação.";
        $tipo_alerta = 'danger';
    } else {
        $nova_senha_hash = '';
        $sucesso = false;

        switch ($action) {
            case 'reset_padrao':
                $senha_padrao = 'mudar123';
                $nova_senha_hash = password_hash($senha_padrao, PASSWORD_DEFAULT);
                break;
            case 'definir_nova':
                $nova_senha = $_POST['nova_senha'] ?? '';
                if (strlen($nova_senha) < 8) {
                    $mensagem = "A nova senha deve ter no mínimo 8 caracteres.";
                    $tipo_alerta = 'danger';
                } else {
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                }
                break;
            default:
                $mensagem = "Ação desconhecida.";
                $tipo_alerta = 'danger';
                break;
        }

        // Se $nova_senha_hash foi criado com sucesso (sem erros de validação anteriores)
        if (!empty($nova_senha_hash)) {
            $stmt = $conexao->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->bind_param("si", $nova_senha_hash, $usuario_id);

            if ($stmt->execute()) {
                $mensagem = "Senha do usuário alterada com sucesso!";
                $tipo_alerta = 'success';
            } else {
                $mensagem = "Falha ao atualizar o banco de dados.";
                $tipo_alerta = 'danger';
            }
        }
    }
}
// --- FIM DO BLOCO DE PROCESSAMENTO ---


// --- BUSCA USUÁRIOS PARA O SELECT (sempre executa para montar a página) ---
$usuarios_disponiveis = [];
$id_admin_logado = $usuario_logado['id'];
$resultado = $conexao->query("SELECT id, nome, email FROM usuarios WHERE id != $id_admin_logado ORDER BY nome ASC");
if ($resultado) {
    while($linha = $resultado->fetch_assoc()) {
        $usuarios_disponiveis[] = $linha;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Senhas de Usuários</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container py-4">
        <h3 class="fw-bold mb-4">Gerenciar Senhas de Usuários</h3>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipo_alerta ?>"><?= $mensagem ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <form action="" method="post">
                <div class="mb-3">
                    <label for="usuario_id" class="form-label">Selecione o Usuário</label>
                    <select name="usuario_id" id="usuario_id" class="form-select" required>
                        <option value="">-- Escolha um usuário --</option>
                        <?php foreach ($usuarios_disponiveis as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['nome']) ?> (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr class="my-4">

                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Opção 1: Resetar para Senha Padrão</strong></p>
                        <p class="form-text">A senha será alterada para: <kbd>mudar123</kbd></p>
                        <button type="submit" name="action" value="reset_padrao" class="btn btn-warning">
                            <i class="bi bi-arrow-counterclockwise"></i> Resetar Senha
                        </button>
                    </div>

                    <div class="col-md-6">
                        <p class="mb-2"><strong>Opção 2: Definir Nova Senha</strong></p>
                        <div class="mb-2">
                            <label for="nova_senha" class="form-label small">Digite a Nova Senha</label>
                            <input type="password" name="nova_senha" id="nova_senha" class="form-control" minlength="8">
                        </div>
                        <button type="submit" name="action" value="definir_nova" class="btn btn-primary">
                            <i class="bi bi-key-fill"></i> Definir Nova Senha
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>