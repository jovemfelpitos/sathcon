<?php
// ARQUIVO: relatorio_bonus.php (O Salão)

$permissoes_necessarias = ['admin'];
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

require_once __DIR__ . '/dados_bonus.php';
?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="icon" href="../img/sathcon1.jpeg" type="image/jpeg">
    <title>Relatório de Bônus — SATH CON</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/main.css">
    
</head>
<body>

    <?php if (file_exists(__DIR__ . '/includes/navbar.php')) include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container py-4">
        <header class="mb-4">
            <h3 class="fw-bold">Relatório de Bônus</h3>
        </header>
        <div class="card p-3 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label for="filter_contrato" class="form-label small">Nº do Contrato</label>
                    <input type="text" name="filter_contrato" id="filter_contrato" class="form-control" value="<?= htmlspecialchars($_GET['filter_contrato'] ?? '') ?>" placeholder="Digite o número...">
                </div>

                <?php if ($isGestor): ?>
                <div class="col-lg-2 col-md-6">
                    <label for="consultor" class="form-label small">Consultor</label>
                    <select name="consultor" id="consultor" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($consultoresUnicos as $c):
                            $selected = ($filterConsultor === $c) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($c)."\" $selected>".htmlspecialchars($c)."</option>";
                        endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="equipe" class="form-label small">Equipe</label>
                    <select name="equipe" id="equipe" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($equipesUnicas as $e):
                            $selected = ($filterEquipe === $e) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($e)."\" $selected>".htmlspecialchars($e)."</option>";
                        endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-lg-2 col-md-6">
                    <label for="data_inicio" class="form-label small">Data Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="data_fim" class="form-label small">Data Fim</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
                </div>
                <div class="col-lg-2 col-md-12">
                    <div class="d-grid"><button class="btn btn-primary" type="submit">Aplicar Filtros</button></div>
                </div>
                <div class="col-lg-1 col-md-12">
                    <div class="d-grid"><a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary" title="Limpar Filtro"><i class="bi bi-x-lg"></i></a></div>
                </div>
            </form>
        </div>

        <section class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6"><div class="card p-3"><h4 class="card-title-custom text-success"><i class="bi bi-check-circle-fill"></i>Confirmado</h4><p class="card-value"><?= fmtMoney($totais['ganha']) ?></p></div></div>
            <div class="col-lg-4 col-md-6"><div class="card p-3"><h4 class="card-title-custom text-warning"><i class="bi bi-hourglass-split"></i>Em Potencial</h4><p class="card-value"><?= fmtMoney($totais['em_potencial']) ?></p></div></div>
            <div class="col-lg-4 col-md-6"><div class="card p-3"><h4 class="card-title-custom text-danger"><i class="bi bi-x-circle-fill"></i>Perdido</h4><p class="card-value"><?= fmtMoney($totais['perdida']) ?></p></div></div>
            <div class="col-lg-6 col-md-6"><div class="card p-3"><h4 class="card-title-custom"><i class="bi bi-calendar-check"></i>Confirmado no Mês</h4><p class="card-value"><?= fmtMoney($totais['mes_atual']) ?></p></div></div>
            <div class="col-lg-6 col-md-6"><div class="card p-3"><h4 class="card-title-custom"><i class="bi bi-calendar-x"></i>Cancelados (90d)</h4><p class="card-value"><?= fmtMoney($totais['ult_cancelados']) ?></p></div></div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-lg-8"><div class="card p-3"><h5 class="mb-3">Performance Mensal (Confirmado)</h5><div class="chart-container"><canvas id="chartMonths"></canvas></div></div></div>
            <div class="col-lg-4"><div class="card p-3"><h5 class="mb-3">Distribuição por Status</h5><div class="chart-container"><canvas id="chartPie"></canvas></div></div></div>
        </section>

        <footer class="text-center text-muted small mt-5">
            <p>Linhas na planilha: <?= (int)($counts['rows_total'] ?? 0) ?> — Linhas após filtro: <?= (int)($counts['rows_filtered'] ?? 0) ?></p>
            <?php if (!empty($gsError)): ?><div class="alert alert-warning small"><strong>Aviso:</strong> <?= htmlspecialchars($gsError) ?></div><?php endif; ?>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const brFormat = num => Number(num).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        Chart.defaults.color = '#e2e2e2';
        Chart.defaults.borderColor = '#444';
        Chart.defaults.font.family = 'Segoe UI, system-ui, sans-serif';

        const ctxMonths = document.getElementById('chartMonths');
        if (ctxMonths) {
            new Chart(ctxMonths, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartMonthsLabels ?? []) ?>,
                    datasets: [{
                        label: 'Bônus (R$)',
                        data: <?= json_encode($chartMonthsData ?? []) ?>,
                        backgroundColor: 'rgba(13, 202, 240, 0.6)',
                        borderColor: 'rgba(13, 202, 240, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: 'rgba(13, 202, 240, 0.9)'
                    }]
                },
                options: {
                    maintainAspectRatio: false, responsive: true,
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#000', titleColor: '#0dcaf0', bodyColor: '#e2e2e2', callbacks: { label: c => `R$ ${brFormat(c.raw)}` } } },
                    scales: { y: { ticks: { color: '#e2e2e2' }, grid: { color: '#444' } }, x: { grid: { display: false }, ticks: { color: '#e2e2e2' } } }
                }
            });
        }
        const ctxPie = document.getElementById('chartPie');
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: ['Confirmado', 'Em Atraso', 'Cancelado'],
                    datasets: [{
                        data: <?= json_encode($pieData ?? []) ?>,
                        backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                        borderColor: '#1e1e1e',
                        borderWidth: 4,
                        hoverOffset: 8
                    }]
                },
                options: {
                    maintainAspectRatio: false, responsive: true, cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20, color: '#e2e2e2' } }, tooltip: { backgroundColor: '#000', callbacks: { label: c => `${c.label}: R$ ${brFormat(c.raw)}` } } }
                }
            });
        }
    });
    </script>
</body>
</html>