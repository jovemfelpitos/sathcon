<?php
// ARQUIVO: criar_usuario.php (O Salão)

// Acesso restrito! Apenas usuários do tipo 'admin' podem ver esta página.
$permissoes_necessarias = ['admin'];
require_once __DIR__ . '/includes/auth_check.php';
// ADICIONADO: Inclui a conexão com o banco de dados
require_once __DIR__ . '/includes/config.php'; 

// =============================================
// NOVO: LÓGICA PARA BUSCAR EQUIPES NO BANCO
// =============================================
$lista_de_equipes = [];
// A consulta busca todos os valores únicos e não nulos da coluna 'equipe'
$resultado = $conexao->query("SELECT DISTINCT equipe FROM usuarios WHERE equipe IS NOT NULL AND equipe != '' ORDER BY equipe ASC");
if ($resultado) {
    while($linha = $resultado->fetch_assoc()) {
        $lista_de_equipes[] = $linha['equipe'];
    }
}
// =============================================
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Criar Novo Usuário</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container py-4">
        <h3 class="fw-bold mb-4">Criar Novo Usuário</h3>

        <?php // --- Mensagens de Feedback ---
        if (isset($_GET['status'])):
            // ... (código das mensagens continua o mesmo) ...
        ?>
            <?php endif; ?>

        <div class="card p-4">
            <form action="processa_cadastro.php" method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" id="nome" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="col-md-12">
                        <label for="senha" class="form-label">Senha (mínimo 8 caracteres)</label>
                        <input type="password" class="form-control" name="senha" id="senha" required minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                        <select name="tipo_usuario" id="tipo_usuario" class="form-select" required>
                            <option value="usuario_padrao" selected>Operador</option>
                            <option value="usuario_gerente">Gerente</option>
                            <option value="usuario_gestor">Gestor</option>
                            <option value="admin">Mestre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="equipe" class="form-label">Equipe (Opcional)</label>
                        <select name="equipe" id="equipe" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($lista_de_equipes as $equipe): ?>
                                <option value="<?= htmlspecialchars($equipe) ?>"><?= htmlspecialchars($equipe) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus-fill"></i> Criar Usuário
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>