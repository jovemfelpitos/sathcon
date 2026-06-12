<?php
// Inclui o "Host" na porta. Ele cuida de TUDO:
// 1. Inicia a sessão
// 2. Verifica se o usuário está logado
// 3. Cria a variável $usuario_logado
require_once __DIR__ . '/includes/auth_check.php';
$isOperador = $usuario_eh_operador ?? ($usuario_logado['tipo'] === 'usuario_padrao');
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Desempenho</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <main class="container py-4">
        <h3 class="mb-3">Desempenho</h3>
        <div class="card p-3 mb-4">
            <div class="row g-3 align-items-end">
                <?php if (!$isOperador): ?>
                <div class="col-lg-2 col-md-4"><label class="form-label">Consultor</label><select id="filtroConsultor" class="form-select"></select></div>
                <div class="col-lg-2 col-md-4"><label class="form-label">Equipe</label><select id="filtroEquipe" class="form-select"></select></div>
                <?php endif; ?>
                <div class="col-lg-2 col-md-4"><label class="form-label">Tabela</label><select id="filtroTabela" class="form-select"><option value="">Todas</option><option value="Pesado">Pesado</option><option value="Leve">Leve</option></select></div>
                <div class="col-lg-2 col-md-4"><label class="form-label">Seguro</label><select id="filtroSeguro" class="form-select"><option value="">Todos</option><option value="Sim">Sim</option><option value="Não">Não</option></select></div>
                <div class="col-lg-2 col-md-4"><label class="form-label">Ano</label><select id="filtroAno" class="form-select"></select></div>
                <div class="col-lg-2 col-md-4 d-grid"><button id="btnAplicar" class="btn btn-primary">Aplicar Filtros</button></div>
            </div>
        </div>

        <div id="loader" class="loader" style="display: none;"></div>

        <div class="row g-3 mb-4 text-center">
            <div class="col-lg col-md-3">
                <div class="card p-3">
                    <h4 class="card-title-custom justify-content-center"><i class="bi bi-file-earmark-text"></i> Total Contratos</h4>
                    <p class="card-value" id="cardContratos">—</p>
                </div>
            </div>
            <div class="col-lg col-md-3">
                <div class="card p-3">
                    <h4 class="card-title-custom justify-content-center"><i class="bi bi-currency-dollar"></i> Valor Vendido</h4>
                    <p class="card-value" id="cardValor">—</p>
                </div>
            </div>
            <div class="col-lg col-md-3">
                <div class="card p-3">
                    <h4 class="card-title-custom justify-content-center"><i class="bi bi-bullseye"></i> Efetividade</h4>
                    <p class="card-value" id="cardEfetividade">—</p>
                </div>
            </div>
            <div class="col-lg col-md-3">
                <div class="card p-3">
                    <h4 class="card-title-custom justify-content-center"><i class="bi bi-shield-check"></i> Com Seguro</h4>
                    <p class="card-value" id="cardSeguro">—</p>
                </div>
            </div>
        </div>

        <div class="card mb-4 p-3"><div id="chart" class="chart-container"></div></div>

        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 id="tituloTabela">Resumo por Consultor</h5>
                <div class="btn-group" role="group" aria-label="Alternar Visão">
                    <input type="radio" class="btn-check" name="btnradio" id="btnConsultor" autocomplete="off" checked>
                    <label class="btn btn-outline-secondary btn-sm" for="btnConsultor">Por Consultor</label>
                    <input type="radio" class="btn-check" name="btnradio" id="btnMes" autocomplete="off">
                    <label class="btn btn-outline-secondary btn-sm" for="btnMes">Por Mês</label>
                </div>
            </div>
            <div class="table-responsive" style="max-height:400px;">
                <table class="table table-dark table-striped table-hover text-center align-middle">
                    <thead id="headerConsultor">
                        <tr>
                            <th>Consultor</th><th>Total Contratos</th><th>Pesados</th><th>Leves</th>
                            <th>Com Seguro</th><th>Ativos</th><th>Valor (R$)</th><th>Efetividade (%)</th>
                        </tr>
                    </thead>
                    <thead id="headerMes" class="d-none">
                        <tr>
                            <th>Mês</th><th>Total Contratos</th><th>Pesados</th><th>Leves</th>
                            <th>Com Seguro</th><th>Ativos</th><th>Valor (R$)</th><th>Efetividade (%)</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaEfetividade"></tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let chartObj = null;
        let dadosCompletos = [];
        let visaoAtual = 'consultor';
        let mesSelecionadoIndex = new Date().getMonth();
        const isOperador = <?= json_encode($isOperador) ?>;
        const labelsMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        const formatCurrency = (value) => parseFloat(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const formatPercent = (value) => `${(value || 0).toFixed(1)}%`;

        async function fetchDados(params = {}) {
            const loader = document.getElementById('loader');
            loader.style.display = 'block';
            try {
                const url = new URL('dados_desempenho.php', window.location.href);
                Object.keys(params).forEach(key => {
                    if (params[key]) url.searchParams.append(key, params[key]);
                });
                const response = await fetch(url);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(`Erro na rede: ${response.statusText} - ${errorData?.details || 'Detalhes indisponíveis.'}`);
                }
                return await response.json();
            } catch (error) {
                console.error("Falha ao buscar dados:", error);
                alert("Não foi possível carregar os dados. Verifique o console para mais detalhes.");
                return null;
            } finally {
                loader.style.display = 'none';
            }
        }

        function popularFiltros(dados) {
            const consultores = [...new Set(dados.map(item => item.consultor))].filter(Boolean).sort();
            const equipes = [...new Set(dados.map(item => item.equipe))].filter(Boolean).sort();
            const anos = [...new Set(dados.map(item => item.data_venda ? item.data_venda.split('/')[2] : null).filter(Boolean))].sort((a, b) => b - a);
            const filtroConsultor = document.getElementById('filtroConsultor');
            const filtroEquipe = document.getElementById('filtroEquipe');
            const filtroAno = document.getElementById('filtroAno');
            if (!isOperador && filtroConsultor && filtroEquipe) {
                filtroConsultor.innerHTML = '<option value="">Todos</option>' + consultores.map(c => `<option value="${c}">${c}</option>`).join('');
                filtroEquipe.innerHTML = '<option value="">Todas</option>' + equipes.map(e => `<option value="${e}">${e}</option>`).join('');
            }
            filtroAno.innerHTML = '<option value="">Todos</option>' + anos.map(a => `<option value="${a}">${a}</option>`).join('');
        }

        function desenharTabelaPorMes() {
            document.getElementById('tituloTabela').innerText = 'Resumo Mensal';
            document.getElementById('headerConsultor').classList.add('d-none');
            document.getElementById('headerMes').classList.remove('d-none');
            const meses = Array.from({ length: 12 }, () => ({ total: 0, pagos: 0, valor: 0, pesados: 0, leves: 0, com_seguro: 0 }));
            dadosCompletos.forEach(item => {
                if (!item.data_venda) return;
                const parts = item.data_venda.split('/');
                if (parts.length === 3) {
                    const mesIndex = parseInt(parts[1], 10) - 1;
                    if (mesIndex >= 0 && mesIndex < 12) {
                        meses[mesIndex].total += parseInt(item.qntd_contrato || 0, 10);
                        meses[mesIndex].valor += parseFloat(item.valor_cota || 0);
                        if(item.tabela === 'Pesado') meses[mesIndex].pesados += parseInt(item.qntd_contrato || 0, 10);
                        else if(item.tabela === 'Leve') meses[mesIndex].leves += parseInt(item.qntd_contrato || 0, 10);
                        if(item.seguro === 'Sim') meses[mesIndex].com_seguro += parseInt(item.qntd_contrato || 0, 10);
                        if (item.situacao.toLowerCase() === 'em dia') meses[mesIndex].pagos++;
                    }
                }
            });

            let tabelaHtml = '';
            meses.forEach((mes, i) => {
                if (mes.total > 0) {
                    const efetividade = mes.total > 0 ? (mes.pagos / mes.total) * 100 : 0;
                    tabelaHtml += `
                        <tr>
                            <td>${labelsMeses[i]}</td><td>${mes.total}</td><td>${mes.pesados}</td>
                            <td>${mes.leves}</td><td>${mes.com_seguro}</td><td>${mes.pagos}</td>
                            <td>${formatCurrency(mes.valor)}</td><td>${formatPercent(efetividade)}</td>
                        </tr>`;
                }
            });
            if (tabelaHtml === '') tabelaHtml = '<tr><td colspan="8" class="text-center">Nenhum dado encontrado para o período.</td></tr>';
            document.getElementById('tabelaEfetividade').innerHTML = tabelaHtml;
        }
        
        function desenharTabelaPorConsultor(mesIndex) {
            mesSelecionadoIndex = mesIndex;
            document.getElementById('tituloTabela').innerText = `Resumo por Consultor - ${labelsMeses[mesIndex]}`;
            document.getElementById('headerMes').classList.add('d-none');
            document.getElementById('headerConsultor').classList.remove('d-none');
            const dadosDoMes = dadosCompletos.filter(item => {
                if (!item.data_venda) return false;
                const parts = item.data_venda.split('/');
                return parts.length === 3 && parseInt(parts[1], 10) === (mesIndex + 1);
            });
            
            const resumoConsultores = {};
            dadosDoMes.forEach(item => {
                const consultor = item.consultor;
                if (!resumoConsultores[consultor]) {
                    resumoConsultores[consultor] = { nome: consultor, totalContratos: 0, contratosAtivos: 0, valorTotal: 0, pesados: 0, leves: 0, comSeguro: 0 };
                }
                resumoConsultores[consultor].totalContratos += parseInt(item.qntd_contrato || 0, 10);
                resumoConsultores[consultor].valorTotal += parseFloat(item.valor_cota || 0);
                if (item.situacao.toLowerCase() === 'em dia') resumoConsultores[consultor].contratosAtivos += parseInt(item.qntd_contrato || 0, 10);
                if (item.tabela === 'Pesado') resumoConsultores[consultor].pesados += parseInt(item.qntd_contrato || 0, 10);
                else if (item.tabela === 'Leve') resumoConsultores[consultor].leves += parseInt(item.qntd_contrato || 0, 10);
                if (item.seguro === 'Sim') resumoConsultores[consultor].comSeguro += parseInt(item.qntd_contrato || 0, 10);
            });

            const consultoresArray = Object.values(resumoConsultores).sort((a, b) => b.valorTotal - a.valorTotal);
            let tabelaHtml = '';
            if (consultoresArray.length === 0) {
                tabelaHtml = '<tr><td colspan="8" class="text-center">Nenhum dado encontrado para este mês.</td></tr>';
            } else {
                consultoresArray.forEach(consultor => {
                    const efetividade = consultor.totalContratos > 0 ? (consultor.contratosAtivos / consultor.totalContratos) * 100 : 0;
                    tabelaHtml += `
                        <tr>
                            <td>${consultor.nome}</td><td>${consultor.totalContratos}</td><td>${consultor.pesados}</td>
                            <td>${consultor.leves}</td><td>${consultor.comSeguro}</td><td>${consultor.contratosAtivos}</td>
                            <td>${formatCurrency(consultor.valorTotal)}</td><td>${formatPercent(efetividade)}</td>
                        </tr>`;
                });
            }
            document.getElementById('tabelaEfetividade').innerHTML = tabelaHtml;
        }

        function atualizarTela(response) {
            if (!response || !response.meta || !response.data) { return; }
            dadosCompletos = response.data;
            const totais = response.meta.totais;
            document.getElementById('cardContratos').innerText = totais.totalContratos || 0;
            document.getElementById('cardValor').innerText = formatCurrency(totais.valorTotal);
            document.getElementById('cardEfetividade').innerText = formatPercent(totais.efetividade);
            document.getElementById('cardSeguro').innerText = totais.totalComSeguro || 0;
            const meses = Array.from({ length: 12 }, () => ({ total: 0 }));
            dadosCompletos.forEach(item => {
                if (!item.data_venda) return;
                const parts = item.data_venda.split('/');
                if (parts.length === 3) {
                    const mesIndex = parseInt(parts[1], 10) - 1;
                    if (mesIndex >= 0 && mesIndex < 12) {
                        meses[mesIndex].total += parseInt(item.qntd_contrato || 0, 10);
                    }
                }
            });
            if (chartObj) chartObj.destroy();
            chartObj = new ApexCharts(document.querySelector("#chart"), {
                chart: { type: 'bar', height: 350, toolbar: { show: true }, foreColor: '#ccc',
                    events: {
                        dataPointSelection: (event, chartContext, config) => {
                            document.getElementById('btnConsultor').checked = true;
                            visaoAtual = 'consultor';
                            desenharTabelaPorConsultor(config.dataPointIndex);
                        }
                    }
                },
                series: [{ name: 'Contratos', data: meses.map(m => m.total) }],
                xaxis: { categories: labelsMeses.map(m => m.substring(0,3)) },
                yaxis: { title: { text: 'Nº de Contratos' }},
                colors: ['#0dcaf0'], grid: { borderColor: '#444' },
                plotOptions: { bar: { distributed: true } },
                tooltip: { y: { formatter: (val) => val + " contratos" } }
            });
            chartObj.render();
            
            if (visaoAtual === 'mes') {
                desenharTabelaPorMes();
            } else {
                desenharTabelaPorConsultor(mesSelecionadoIndex);
            }
        }

        async function carregarDadosIniciaisEAtualizar() {
            const responseInicial = await fetchDados(); 
            if (responseInicial) {
                popularFiltros(responseInicial.data);
                const anoAtual = new Date().getFullYear().toString();
                document.getElementById('filtroAno').value = anoAtual;
                const responseTela = await fetchDados({ ano: anoAtual });
                if (responseTela) atualizarTela(responseTela); 
            }
        }
        
        document.getElementById('btnConsultor').addEventListener('click', () => {
            if (visaoAtual !== 'consultor') {
                visaoAtual = 'consultor';
                desenharTabelaPorConsultor(mesSelecionadoIndex);
            }
        });
        document.getElementById('btnMes').addEventListener('click', () => {
            if (visaoAtual !== 'mes') {
                visaoAtual = 'mes';
                desenharTabelaPorMes();
            }
        });

        document.getElementById('btnAplicar').addEventListener('click', async () => {
            const filtroConsultor = document.getElementById('filtroConsultor');
            const filtroEquipe = document.getElementById('filtroEquipe');
            const params = {
                consultor: filtroConsultor ? filtroConsultor.value : '',
                equipe: filtroEquipe ? filtroEquipe.value : '',
                tabela: document.getElementById('filtroTabela').value,
                seguro: document.getElementById('filtroSeguro').value,
                ano: document.getElementById('filtroAno').value
            };
            const responseFiltrada = await fetchDados(params);
            if (responseFiltrada) atualizarTela(responseFiltrada);
        });

        document.addEventListener('DOMContentLoaded', carregarDadosIniciaisEAtualizar);
    </script>
</body>
</html>
