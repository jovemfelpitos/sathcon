<?php
// Toda a sua lógica PHP de login, verificação de erro e sessão continua aqui.
// Nenhuma alteração foi feita nesta parte.
session_start();
include_once('includes/config.php');

$erro = ""; 

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    $sql = "SELECT id, nome, email, senha, tipo_usuario, equipe, url_foto_perfil FROM usuarios WHERE email = ? AND status = 1 LIMIT 1";
    
    $stmt = $conexao->prepare($sql);
    if ($stmt === false) { die("Erro ao preparar a consulta: " . $conexao->error); }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($senha_digitada, $usuario['senha'])) {
            session_regenerate_id(true); 
            $_SESSION['id']              = $usuario['id'];
            $_SESSION['nome']            = $usuario['nome'];
            $_SESSION['email']           = $usuario['email'];
            $_SESSION['tipo_usuario']    = $usuario['tipo_usuario'];
            $_SESSION['equipe']          = $usuario['equipe'];
            $_SESSION['url_foto_perfil'] = $usuario['url_foto_perfil'];
            header('Location: dashboard.php');
            exit();
        } else {
            $erro = "Credenciais inválidas. Verifique seu email e senha.";
        }
    } else {
        $erro = "Credenciais inválidas. Verifique seu email e senha.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | ContratiX</title>
    <link rel="icon" type="image/png" href="assets/img/icon_contratix.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html {
            font-size: 13px;
        }
    
        body {
            background-color: #fff;
            min-height: 100vh;
        }
        .login-container-row {
            min-height: 100vh;
        }
        .login-form-panel {
            background-color: #343a40;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
        }
        .form-container {
            max-width: 400px;
            width: 100%;
        }
        .login-branding-panel {
            background-image: url('assets/img/login_background.png');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            min-height: 100vh;
        }
        .quote-box {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 0.5rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .quote-box h2 { font-weight: 700; font-size: 2.5rem; }
        .quote-box p { font-size: 0.9rem; opacity: 0.8; }
        .input-group-text { background-color: #495057; border-color: #6c757d; }
        .form-control { background-color: #495057; border-color: #6c757d; color: white; }
        .form-control:focus {
            background-color: #495057;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            color: white;
        }
        .form-control::placeholder { color: #adb5bd; }
        .social-icons a { color: #adb5bd; transition: color 0.2s; font-size: 1.2rem; }
        .social-icons a:hover { color: white; }

        @media (max-width: 991.98px) {
            .login-branding-panel {
                min-height: 40vh;
            }
            .login-container-row {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid g-0">
        <div class="row g-0 login-container-row">
            <div class="col-lg-8 login-branding-panel">            </div>
            <div class="col-lg-4 login-form-panel">
                <div class="form-container mx-auto">
                    <div class="text-center mb-5">
                        <img src="assets/img/logo_contratix.png" alt="Logo Sath Con" style="width: 200px;">
                    </div>
                    <h4 class="fw-bold mb-2">Log In</h4>
                    <p class="text-muted mb-4">Utilize as suas credenciais para acessar o sistema.</p>

                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger"><?= $erro ?></div>
                    <?php endif; ?>

                    <form action="index.php" method="POST">
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="email" class="form-control" name="email" placeholder="Usuário (e-mail)" required>
                        </div>
                        <div class="input-group mb-4">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="senha" placeholder="Senha" required>
                        </div>
                        <div class="d-grid mb-4">
                            <button class="btn btn-info fw-bold" type="submit" name="submit">Entrar</button>
                        </div>
                    </form>
                    <hr class="my-4">
                    <div class="text-center text-muted social-icons">
                        <p class="mb-2 small">Conecte-se conosco:</p>
                        <a href="https://www.instagram.com/sath_gold/#" class="me-3"><i class="bi bi-instagram"></i></a>
                        <a href="https://sathgold.com.br/"><i class="bi bi-globe"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>