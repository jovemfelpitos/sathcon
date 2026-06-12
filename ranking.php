<?php
// ARQUIVO: ranking.php (O Salão)
require_once __DIR__ . '/dados_ranking.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Ranking Top 3º</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../img/sathcon1.jpeg" type="image/jpeg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/main.css">
<style>
    /* CSS particular desta página para o pódio */
    .main-grid{display:grid;grid-template-columns:1fr 480px;gap:24px;margin-top:24px}
    @media (max-width: 1000px){ .main-grid{grid-template-columns:1fr} }

    .podio-wrap{
        display:flex;
        align-items:flex-end;
        justify-content:center;
        gap: 1rem;
        padding:18px;
        width: 100%;
        min-height: 350px;
    }

    .podio-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        max-width: 140px;
    }

    .barra{
        position:relative;
        width: 110px;
        border-radius:16px 16px 8px 8px;
        display:flex;
        align-items:center;
        justify-content:center;
        transform:translateZ(0);
        min-height: 100px;
    }
    
    .rank-num{
        font-weight:900;
        font-size:58px;
        line-height:1;
    }
    
    .medal-wrap{position:absolute;top:-48px;left:50%;transform:translateX(-50%);font-size:28px;}
    .coroa{position:absolute;top:-84px;left:50%;transform:translateX(-50%) rotate(-8deg);font-size:34px;}

    .medal-wrap .bi, .coroa .bi {
        filter: drop-shadow(0 3px 5px rgba(0,0,0,0.5));
    }
    
    /* NOVAS REGRAS: Cores para os ícones */
    .gold .medal-wrap .bi, .gold .coroa .bi { color: #D4AF37; }
    .silver .medal-wrap .bi { color: #C0C0C0; }
    .bronze .medal-wrap .bi { color: #CD7F32; }
    
    .info{
        text-align:center;
        margin-top:10px;
        width: 100%;
    }
    .info .nome{
        font-weight:700;
        white-space: normal;
        word-wrap: break-word;
        height: 2.6em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.9rem;
    }
    
    .gold{background:linear-gradient(180deg,#FFD700,#E6C200); color:#1a1200}
    .silver{background:linear-gradient(180deg,#C0C0C0,#9e9e9e); color:#111}
    .bronze{background:linear-gradient(180deg,#CD7F32,#9b5b2a); color:#111}

    .resumo-item{margin-bottom:12px;}
    .resumo-item .titulo{font-weight:800;margin-bottom:6px}
</style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container py-4">
        <header class="mb-4">
            <h3 class="fw-bold">🏆 Ranking de Vendas</h3>
            <p class="text-muted">Período: <strong><?= $dataDe->format('d/m/Y') ?></strong> a <strong><?= $dataAte->format('d/m/Y') ?></strong> — Total de <strong><?= count($rank) ?></strong> consultores no ranking</p>
        </header>

        <div class="card p-3 mb-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">De</label>
                    <input type="date" name="dataDe" class="form-control" value="<?= htmlspecialchars($dataDe->format('Y-m-d')) ?>">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Até</label>
                    <input type="date" name="dataAte" class="form-control" value="<?= htmlspecialchars($dataAte->format('Y-m-d')) ?>">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Equipe</label>
                    <select name="equipe" class="form-select">
                        <option value="">Todas as equipes</option>
                        <?php foreach($equipesList as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= (strcasecmp($filterEquipe, $e) === 0) ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                    <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-secondary" title="Limpar Filtros"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>

        <div class="main-grid">
            <div class="podio-col card p-3">
                <h5 class="mb-3">Top 3 Consultores</h5>
                <div class="podio-wrap">
                    <?php foreach($vizOrder as $idxVisual):
                        $nome  = $labels[$idxVisual];
                        $total = $dados[$idxVisual];
                        $qtd   = $counts[$idxVisual];
                        $realPos = 0;
                        if ($idxVisual == 1) $realPos = 2; // Silver
                        if ($idxVisual == 0) $realPos = 1; // Gold
                        if ($idxVisual == 2) $realPos = 3; // Bronze
                        
                        $classe = $realPos===1?'gold':($realPos===2?'silver':'bronze');
                        
                        $icone_medalha = 'bi-patch-check-fill'; // Padrão para 3º lugar
                        if ($realPos === 1) $icone_medalha = 'bi-trophy-fill';
                        if ($realPos === 2) $icone_medalha = 'bi-award-fill';

                        $maxTotal = max(1.0, ...$dados);
                        $altura = $total > 0 ? max(120, intval(($total / $maxTotal) * 220)) : 120;
                    ?>
                    <div class="podio-item">
                        <div class="barra <?= $classe ?>" style="height: <?= $altura ?>px;">
                            <?php if($realPos===1): ?>
                                <div class="coroa"><i class="bi bi-crown-fill"></i></div>
                            <?php endif; ?>
                            <div class="medal-wrap"><i class="bi <?= $icone_medalha ?>"></i></div>
                            <div class="rank-num"><?= $realPos ?></div>
                        </div>
                        <div class="info">
                            <div class="nome" title="<?= htmlspecialchars($nome) ?>"><?= htmlspecialchars($nome) ?></div>
                            <div class="valor fw-bold"><?= brl($total) ?></div>
                            <div class="text-muted small"><?= $qtd ?> vendas</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="resumo-col card p-3">
                <h5 class="mb-3">Detalhes do Top 3</h5>
                <?php for($i=0;$i<3;$i++):
                    $nome = $labels[$i];
                    $total = $dados[$i];
                    $qtd = $counts[$i];
                    $foto = $fotosConsultoresNorm[normalizeName($nome)] ?? 'assets/img/default.png';
                ?>
                <div class="resumo-item" style="display:flex;gap:18px;align-items:center;padding:12px 0;">
                    <div>
                        <img src="<?= $foto ?>" alt="<?= htmlspecialchars($nome) ?>" style="width:75px;height:75px;border-radius:50%;object-fit:cover;border:3px solid var(--border-color);">
                    </div>
                    <div style="flex:1">
                        <div class="titulo" style="font-size:1.1rem;"><?= ($i+1) ?>º <?= htmlspecialchars($nome) ?></div>
                        <div class="text-muted small"><?= $qtd ?> vendas</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700;font-size:1.1rem;color:var(--accent-color);"><?= brl($total) ?></div>
                    </div>
                </div>
                <?php if($i<2): ?><hr style="border-color: rgba(255,255,255,0.1);"><?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', ()=>{
            const temTop1 = <?= json_encode(($dados[0] ?? 0) > 0) ?>;
            if(temTop1) {
                // Efeito de confete sutil
                const duration = 2 * 1000;
                const end = Date.now() + duration;
                (function frame() {
                    confetti({
                        particleCount: 2, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#FFD700','#C0C0C0']
                    });
                    confetti({
                        particleCount: 2, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#FFD700','#C0C0C0']
                    });
                    if (Date.now() < end) {
                        requestAnimationFrame(frame);
                    }
                }());
            }
        });
    </script>
</body>
</html>