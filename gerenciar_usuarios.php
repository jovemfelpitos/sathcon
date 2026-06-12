<?php
// ARQUIVO: gerenciar_usuarios.php (VERSÃO UNIFICADA E COM EQUIPES DINÂMICAS)

$permissoes_necessarias = ['admin'];
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

$mensagem = '';
$tipo_alerta = '';

// --- BLOCO DE PROCESSAMENTO DO FORMULÁRIO (A "COZINHA") ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $equipe = empty($_POST['equipe']) ? null : trim($_POST['equipe']);
    $status = $_POST['status'] ?? '0';

    if (empty($id) || empty($nome) || empty($email) || empty($tipo_usuario)) {
        $mensagem = "Erro: Campos obrigatórios não podem estar vazios.";
        $tipo_alerta = 'danger';
    } else {
        // Verifica se o novo e-mail já está em uso por OUTRO usuário
        $stmt_check = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $mensagem = "Erro: O e-mail informado já está em uso por outro usuário.";
            $tipo_alerta = 'danger';
        } else {
            // Se o e-mail estiver livre, atualiza os dados do usuário no banco
            $stmt_update = $conexao->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo_usuario = ?, equipe = ?, status = ? WHERE id = ?");
            $stmt_update->bind_param("ssssii", $nome, $email, $tipo_usuario, $equipe, $status, $id);

            if ($stmt_update->execute()) {
                $mensagem = "Usuário atualizado com sucesso!";
                $tipo_alerta = 'success';
            } else {
                $mensagem = "Falha ao atualizar o banco de dados.";
                $tipo_alerta = 'danger';
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// --- BUSCA DADOS PARA A PÁGINA ---
// 1. Lista de todos os usuários para a tabela principal
$lista_usuarios = [];
$resultado_usuarios = $conexao->query("SELECT id, nome, email, tipo_usuario, equipe, status FROM usuarios ORDER BY nome ASC");
if ($resultado_usuarios) {
    while($linha = $resultado_usuarios->fetch_assoc()) {
        $lista_usuarios[] = $linha;
    }
}

// 2. Lista de todas as equipes para o menu <select>
$lista_de_equipes = [];
$resultado_equipes = $conexao->query("SELECT DISTINCT equipe FROM usuarios WHERE equipe IS NOT NULL AND equipe != '' ORDER BY equipe ASC");
if ($resultado_equipes) {
    while($linha = $resultado_equipes->fetch_assoc()) {
        $lista_de_equipes[] = $linha['equipe'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">Gerenciar Usuários</h3>
            <div class="btn-group" role="group">
                <a href="gerenciar_senhas.php" class="btn btn-warning">
                    <i class="bi bi-shield-lock"></i> Gerenciar Senhas
                </a>
                <a href="criar_usuario.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Criar Novo Usuário
                </a>
            </div>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th><th>Email</th><th>Tipo</th><th>Equipe</th><th>Status</th><th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td><?= htmlspecialchars(traduzirTipoUsuario($usuario['tipo_usuario'])) ?></td>
                                <td><?= htmlspecialchars($usuario['equipe'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($usuario['status'] == 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal"
                                            data-user-id="<?= $usuario['id'] ?>"
                                            data-user-nome="<?= htmlspecialchars($usuario['nome']) ?>"
                                            data-user-email="<?= htmlspecialchars($usuario['email']) ?>"
                                            data-user-tipo="<?= $usuario['tipo_usuario'] ?>"
                                            data-user-equipe="<?= htmlspecialchars($usuario['equipe'] ?? '') ?>"
                                            data-user-status="<?= $usuario['status'] ?>">
                                        <i class="bi bi-pencil-fill"></i> Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label for="edit-nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="edit-nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-tipo" class="form-label">Tipo de Usuário</label>
                            <select class="form-select" id="edit-tipo" name="tipo_usuario" required>
                                <option value="usuario_padrao">Operador</option>
                                <option value="usuario_gerente">Gerente</option>
                                <option value="usuario_gestor">Gestor</option>
                                <option value="admin">Mestre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-equipe" class="form-label">Equipe</label>
                            <select class="form-select" id="edit-equipe" name="equipe">
                                <option value="">Nenhuma</option>
                                <?php foreach ($lista_de_equipes as $equipe): ?>
                                    <option value="<?= htmlspecialchars($equipe) ?>"><?= htmlspecialchars($equipe) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-status" class="form-label">Status</label>
                            <select class="form-select" id="edit-status" name="status" required>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-nome');
                const userEmail = button.getAttribute('data-user-email');
                const userTipo = button.getAttribute('data-user-tipo');
                const userEquipe = button.getAttribute('data-user-equipe');
                const userStatus = button.getAttribute('data-user-status');
                const modalTitle = editUserModal.querySelector('.modal-title');
                const idInput = editUserModal.querySelector('#edit-id');
                const nomeInput = editUserModal.querySelector('#edit-nome');
                const emailInput = editUserModal.querySelector('#edit-email');
                const tipoInput = editUserModal.querySelector('#edit-tipo');
                const equipeInput = editUserModal.querySelector('#edit-equipe');
                const statusInput = editUserModal.querySelector('#edit-status');
                modalTitle.textContent = 'Editar Usuário: ' + userName;
                idInput.value = userId;
                nomeInput.value = userName;
                emailInput.value = userEmail;
                tipoInput.value = userTipo;
                equipeInput.value = userEquipe;
                statusInput.value = userStatus;
            });
        }
    </script>
</body>
</html>