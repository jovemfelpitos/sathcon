<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark fixed-top">
      <div class="container-fluid">
        <button class="btn btn-outline-light me-2" id="toggleSidebar">
          <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand" href="dashboard.php"><img src="assets/img/logo_contratix.png" alt="Logo Sath Con" style="width: 150px;"></a>

        <?php
        // Prepara o subtítulo com a função e equipe
        $isOperador = $usuario_eh_operador ?? ($usuario_logado['tipo'] === 'usuario_padrao');
        $nomeExibicao = $isOperador ? 'Operador' : ($usuario_logado['nome'] ?? 'Usuário');
        $subtitulo = $isOperador ? 'Conta restrita' : ($usuario_logado['tipo_bonito'] ?? '');
        if (!$isOperador && !empty($usuario_logado['equipe'])) {
            $subtitulo .= ' - ' . htmlspecialchars($usuario_logado['equipe']);
        }
        ?>

        <div class="d-flex ms-auto align-items-center">
            
            <?php 
            // O BOTÃO DO ROBÔ AGORA FICA NA BARRA DO TOPO!
            // Verifica se o usuário logado tem permissão de gestor/admin
            if (isset($usuario_logado['tipo']) && in_array($usuario_logado['tipo'], ['admin', 'usuario_gestor'])): 
            ?>
                <button id="btnSincronizarPlanilha" class="btn btn-outline-info btn-sm me-3" title="Rodar Robô de Sincronização">
                    <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Sincronizar</span>
                </button>
            <?php endif; ?>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    
                    <img src="<?= htmlspecialchars($usuario_logado['url_foto'] ?? 'assets/img/default.png') ?>" alt="Foto do Usuário" width="38" height="38" class="rounded-circle me-2">
                    
                    <div class="user-info d-none d-md-block">
                        <strong class="user-name"><?= htmlspecialchars($nomeExibicao) ?></strong>
                        <small class="user-role"><?= $subtitulo ?></small>
                    </div>

                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow dropdown-menu-end" style="min-width: 220px;">
                    <li><h6 class="dropdown-header">Bem-vindo!</h6></li>
                    <li>
                        <a class="dropdown-item" href="meu_perfil.php">
                            <i class="bi bi-person-circle me-2"></i> Alterar Senha / Alterar Foto
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
      </div>
    </nav>

    <div id="sidebar" class="bg-dark text-white">
      <ul class="nav flex-column pt-3">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-house-door"></i> Início</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-speedometer2"></i> Painel de Clientes</a></li>
        
        <li class="nav-item">
          <a class="nav-link text-white d-flex align-items-center submenu-toggle" href="#">
            <i class="bi bi-bar-chart me-2"></i> Relatórios
            <i class="bi bi-chevron-right ms-auto submenu-icon"></i>
          </a>
          <div class="submenu">
            <ul class="nav flex-column ms-3">
              <li class="nav-item"><a class="nav-link text-white" href="desempenho.php">Desempenho</a></li>
              <?php if (!$isOperador): ?>
              <li class="nav-item"><a class="nav-link text-white" href="ranking.php"> Ranking</a></li>
              <?php endif; ?>
              <?php if ($usuario_logado['tipo'] === 'admin'): ?>
              <li class="nav-item"><a class="nav-link text-white" href="relatorio_bonus.php"> Bônus</a></li>
              <?php endif; ?>
            </ul>
          </div>
        </li>

        <?php if ($usuario_logado['tipo'] === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link text-white d-flex align-items-center submenu-toggle" href="#">
                <i class="bi bi-gear-fill me-2"></i> Administrativo
                <i class="bi bi-chevron-right ms-auto submenu-icon"></i>
              </a>
              <div class="submenu">
                <ul class="nav flex-column ms-3"> 
                  <li class="nav-item"><a class="nav-link text-white" href="criar_usuario.php"><i class="bi bi-person-plus"></i> Cadastro Usuário</a></li>
                  <li class="nav-item"><a class="nav-link text-white" href="gerenciar_senhas.php"><i class="bi bi-shield-lock"></i> Gerenciar Senhas</a></li>
                  <li class="nav-item"><a class="nav-link text-white" href="gerenciar_usuarios.php"><i class="bi bi-people-fill"></i> Gerenciar Usuários</a></li>
                </ul>
              </div>
            </li>
        <?php endif; ?>

        <li class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
      </ul>
    </div>

    <style>
      body { padding-top: 56px; transition: margin-left 0.3s; }
      #sidebar { width: 250px; position: fixed; top: 56px; left: -250px; height: 100%; overflow-y: auto; transition: left 0.3s; z-index: 1040; }
      #sidebar.active { left: 0; }
      body.shifted { margin-left: 250px; }
      .submenu { display: none; }
      .submenu.open { display: block; }
      .submenu-icon { transition: transform 0.3s ease; }
      .submenu-icon.rotate { transform: rotate(90deg); }
      .user-dropdown-toggle { padding: 0.5rem 0.75rem; border-radius: 0.5rem; transition: background-color 0.2s ease-in-out; }
      .user-dropdown-toggle:hover { background-color: rgba(255, 255, 255, 0.1); }
      .user-dropdown-toggle .user-info { line-height: 1.2; text-align: left; color: var(--text-color, #fff); }
      .user-dropdown-toggle .user-name { display: block; font-weight: 600; }
      .user-dropdown-toggle .user-role { font-size: 0.8em; color: var(--muted-color, #adb5bd); }
      .user-dropdown-toggle::after { display: none; }
      .user-dropdown-toggle img { object-fit: cover; }
    </style>

    <script>
      document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");

        if(toggleBtn && sidebar) {
            toggleBtn.addEventListener("click", function() {
              sidebar.classList.toggle("active");
              document.body.classList.toggle("shifted");
            });
        }

        const submenuToggles = document.querySelectorAll(".submenu-toggle");

        submenuToggles.forEach(function(toggle) {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                
                const submenu = toggle.nextElementSibling;
                const submenuIcon = toggle.querySelector(".submenu-icon");
                
                submenu.classList.toggle("open");
                if (submenuIcon) {
                    submenuIcon.classList.toggle("rotate");
                }
            });
        });
      });
    </script>
</body>
</html>
