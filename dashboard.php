<?php
// Inclui o "Host" na porta. Ele cuida de TUDO:
// 1. Inicia a sessão
// 2. Verifica se o usuário está logado (redireciona para index.php se não estiver)
// 3. Cria a variável $usuario_logado com os dados da sessão
require_once __DIR__ . '/includes/auth_check.php';
$isOperador = $usuario_eh_operador ?? ($usuario_logado['tipo'] === 'usuario_padrao');
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel de Clientes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        table {
            font-size: 11px
        }    
    
        .dt-buttons .btn {
            margin-left: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        /* Ajustes para o DataTables se integrar melhor ao tema */
        div.dataTables_wrapper div.dataTables_length select,
        div.dataTables_wrapper div.dataTables_filter input {
            background-color: var(--input-bg); border-color: var(--border-color); color: var(--text-color);
        }
        .dt-buttons .btn-secondary {
            background-color: var(--input-bg); border-color: var(--border-color);
        }
        /* CSS ADICIONAL PARA ESTILO DA TABELA */
        #tabelaClientes th, #tabelaClientes td {
            vertical-align: middle; padding: 0.9rem 0.75rem;
        }
        #tabelaClientes thead th {
            text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px;
        }
        #tabelaClientes tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        /* Estilo para os <p> dentro do modal */
        .modal-body p {
            color: #FFF; font-weight: 500; margin: 0;
        }
        .modal-body strong {
            color: #adb5bd; font-weight: 400; font-size: 0.9em;
        }
        /* Estilo para texto de consultor inativo na tabela */
        .text-inactive {
            opacity: 0.6;
            font-style: italic;
        }

        /* CSS ADICIONADO: Estilo do Overlay de Carregamento */
        .table-container {
            position: relative; /* Essencial para o posicionamento do overlay */
        }
        .table-loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(33, 37, 41, 0.7); /* Cor de fundo escura com transparência */
            z-index: 10; /* Garante que fique por cima da tabela */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--bs-card-border-radius);
            backdrop-filter: blur(2px); /* Efeito de desfoque (opcional) */
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <h3 class="mb-3 fw-bold">Painel de Clientes</h3>
        
        <div id="cardsResumo">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><div class="card p-3 text-center"><h4 class="card-title-custom justify-content-center"><i class="bi bi-currency-dollar"></i> Total Vendas</h4><p class="card-value" id="cardValor">—</p></div></div>
                <div class="col-md-6"><div class="card p-3 text-center"><h4 class="card-title-custom justify-content-center"><i class="bi bi-bullseye"></i> Efetividade</h4><p class="card-value" id="cardEfet">—</p></div></div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6"><div class="card p-3 text-center"><h4 class="card-title-custom justify-content-center"><i class="bi bi-file-earmark-text"></i> Total Contratos</h4><p class="card-value" id="cardTotal">—</p></div></div>
                <div class="col-lg-3 col-md-6"><div class="card p-3 text-center"><h4 class="card-title-custom justify-content-center text-success"><i class="bi bi-check-circle"></i> Em Dia</h4><p class="card-value" id="cardEmDia">—</p></div></div>
                <div class="col-lg-3 col-md-6"><div class="card p-3 text-center"><h4 class="card-title-custom justify-content-center text-warning"><i class="bi bi-exclamation-triangle"></i> Inadimplentes</h4><p class="card-value" id="cardAtrasados">—</p></div></div>
                <div class="col-lg-3 col-md-6"><div class="card p-3 text-center"><h4 class="card-title-custom justify-content-center text-danger"><i class="bi bi-x-circle"></i> Cancelados</h4><p class="card-value" id="cardCancelados">—</p></div></div>
            </div>
        </div>
        
        <div class="card p-3 mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label for="filtroStatus" class="form-label">Status</label>
                    <select id="filtroStatus" class="form-select">
                        <option value="todos">Todos</option><option value="em_dia">Em Dia</option><option value="atrasados">Atrasados</option><option value="cancelados">Cancelados</option>
                    </select>
                </div>
                <?php if (!$isOperador): ?>
                    <div class="col-lg-3 col-md-4">
                        <label for="filtroConsultor" class="form-label">Consultor</label>
                        <select id="filtroConsultor" class="form-select"><option value="">Carregando...</option></select>
                    </div>
                <?php endif; ?>
                <div class="col-lg-2 col-md-4">
                    <label for="filtroDataDe" class="form-label">Data De</label>
                    <input type="date" id="filtroDataDe" class="form-control">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="filtroDataAte" class="form-label">Data Até</label>
                    <input type="date" id="filtroDataAte" class="form-control">
                </div>
                <div class="col-lg-1 col-md-4">
                    <label for="filtroParcela" class="form-label">Parc.</label>
                    <input type="number" id="filtroParcela" class="form-control" min="1" max="12">
                </div>
                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button id="btnAplicar" class="btn btn-primary w-100">Aplicar</button>
                    <button id="btnLimpar" class="btn btn-outline-secondary" title="Limpar Filtros"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-lg-6 col-md-8">
                    <label for="filtroBusca" class="form-label">Busca Rápida (nome/contrato)</label>
                    <input id="filtroBusca" class="form-control" placeholder="Buscar...">
                </div>
                <?php if (in_array($usuario_logado['tipo'], ['admin', 'usuario_gestor', 'usuario_gerente'])): ?>
                <div class="col-lg-3 col-md-4">
                    <div class="form-check form-switch pt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="filtroIncluirInativos">
                        <label class="form-check-label" for="filtroIncluirInativos">Incluir Consultores Inativos</label>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card p-3 table-container">
            <div id="table-loader-overlay" class="table-loader-overlay" style="display: none;">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tabelaClientes" class="table table-dark table-striped table-hover table-sm" style="width:100%">
                    <thead>
                        <tr>
                            <th>Data Venda</th> <?php if (!$isOperador): ?><th>Consultor</th><?php endif; ?> <th>Contrato</th>
                            <th>Parcelas</th> <th>Valor</th> <th>Situação</th>
                            <th>Cliente</th> <th>Telefone</th> <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientDetailsModalLabel">Detalhes do Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-loader" class="text-center">
                        <div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div>
                    </div>
                    <div id="modal-content-details" class="details-grid" style="display: none;">
                        <div class="detail-item"><strong>Nome do Cliente</strong> <span id="modal-nome_cliente"></span></div>
                        <div class="detail-item"><strong>Contrato</strong> <span id="modal-numero_contrato"></span></div>
                        <?php if (!$isOperador): ?>
                        <div class="detail-item"><strong>Email Consultor</strong> <span id="modal-email_consultor"></span></div>
                        <?php endif; ?>
                        <div class="detail-item"><strong>Telefone</strong> <span id="modal-telefone_cliente"></span></div>
                        <div class="detail-item"><strong>Data da Venda</strong> <span id="modal-data_venda"></span></div>
                        <div class="detail-item"><strong>Valor da Cota</strong> <span id="modal-valor_cota"></span></div>
                        <div class="detail-item"><strong>Parcelas Pagas</strong> <span id="modal-parcelas_pagas_atual"></span></div>
                        <div class="detail-item"><strong>Situação</strong> <span id="modal-status_atual"></span></div>
                        <?php if (!$isOperador): ?>
                        <div class="detail-item"><strong>Consultor</strong> <span id="modal-nome_consultor"></span></div>
                        <div class="detail-item"><strong>Status do Consultor</strong> <span id="modal-status_consultor"></span></div>
                        <div class="detail-item"><strong>Equipe</strong> <span id="modal-equipe"></span></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script>
        let table = null;
        let filtrosPopulares = false;
        const isOperador = <?= json_encode($isOperador) ?>;

        function formatBRL(v) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0); }
        function formatText(v) { return v || 'N/A'; }

        function atualizarCards(totais) {
            document.getElementById('cardTotal').textContent = totais.totalContratos ?? 0;
            document.getElementById('cardEmDia').textContent = totais.emDia ?? 0;
            document.getElementById('cardAtrasados').textContent = totais.atrasados ?? 0;
            document.getElementById('cardCancelados').textContent = totais.cancelados ?? 0;
            document.getElementById('cardValor').textContent = formatBRL(totais.valorTotalVendas ?? 0);
            document.getElementById('cardEfet').textContent = totais.efetividade ? Number(totais.efetividade).toFixed(2)+'%' : '0.00%';
        }

        function buildActions(row) {
            const phone = (row.telefone_cliente || '').replace(/\D/g,'');
            const msg = encodeURIComponent(`Olá ${row.nome_cliente || ''}, sobre o contrato ${row.numero_contrato || ''}`);
            const whatsappBtn = phone ? `<a target="_blank" href="https://wa.me/55${phone}?text=${msg}" class="btn btn-success btn-sm" title="Chamar no WhatsApp"><i class="bi bi-whatsapp"></i></a>` : '';
            const detailsBtn = `<button type="button" class="btn btn-primary btn-sm" title="Ver Detalhes" data-bs-toggle="modal" data-bs-target="#clientDetailsModal" data-row-id="${row.id}"><i class="bi bi-eye"></i></button>`;
            return `<div class="d-flex gap-1 justify-content-center">${detailsBtn} ${whatsappBtn}</div>`;
        }

        async function fetchDados(params = {}) {
            const url = new URL('dados_clientes.php', window.location.href);
            Object.keys(params).forEach(k=>{
                if (params[k] != null && String(params[k]) !== '') url.searchParams.set(k, params[k]);
            });
            const res = await fetch(url);
            if (!res.ok) { console.error('Erro ao buscar dados', res.status); return null; }
            return await res.json();
        }
        
        function popularFiltros(dadosCompletos) {
            if (isOperador) return;
            const consultores = [...new Set(dadosCompletos.map(item => item.nome_consultor))].filter(Boolean).sort();
            const filtroConsultor = document.getElementById('filtroConsultor');
            if (!filtroConsultor) return;
            let optionsHtml = '<option value="">Todos</option>';
            consultores.forEach(c => {
                optionsHtml += `<option value="${c}">${c}</option>`;
            });
            filtroConsultor.innerHTML = optionsHtml;
        }

        async function carregarTabela() {
            const loader = document.getElementById('table-loader-overlay');
            loader.style.display = 'flex';

            const filtroInativosToggle = document.getElementById('filtroIncluirInativos');
            const filtroConsultor = document.getElementById('filtroConsultor');
            const params = {
                status: document.getElementById('filtroStatus').value,
                consultor: filtroConsultor ? filtroConsultor.value : '',
                dataDe: document.getElementById('filtroDataDe').value,
                dataAte: document.getElementById('filtroDataAte').value,
                parcela: document.getElementById('filtroParcela').value,
                busca: document.getElementById('filtroBusca').value,
                incluirInativos: filtroInativosToggle ? filtroInativosToggle.checked : false
            };

            try {
                const resp = await fetchDados(params);
                if (!resp) return;
                
                if (!filtrosPopulares && resp.data.length > 0) {
                    popularFiltros(resp.data);
                    filtrosPopulares = true;
                }
                
                atualizarCards(resp.meta.totais ?? {});
                
                const tableColumns = [
                    { data: 'data_venda' },
                    ...(!isOperador ? [{ data: 'nome_consultor', render: (data, type, row) => (row.status_consultor === 'inativo') ? `<span class="text-inactive" title="Consultor Inativo">${data}</span>` : data }] : []),
                    { data: 'numero_contrato' },
                    { data: 'parcelas_pagas_atual' },
                    { data: 'valor_cota', render: val => formatBRL(val) },
                    { data: 'status_atual' },
                    { data: 'nome_cliente' },
                    { data: 'telefone_cliente' },
                    { data: null, orderable: false, searchable: false, render: (data,type,row) => buildActions(row) }
                ];
                const statusColumnIndex = isOperador ? 4 : 5;
                const numericColumnIndexes = isOperador ? [2, 3] : [3, 4];
                const actionsColumnIndex = isOperador ? 7 : 8;
                const exportColumnIndexes = isOperador ? [0, 1, 2, 3, 4, 5, 6] : [0, 1, 2, 3, 4, 5, 6, 7];

                if (!table) {
                    table = $('#tabelaClientes').DataTable({
                        data: resp.data || [],
                        columns: tableColumns,
                        // ---- COLUNAS E ESTILOS EXTRAS ----
                        columnDefs: [
                            { targets: statusColumnIndex, render: (data, type, row) => {
                                if (type === 'display') {
                                    const situacao = String(data).toLowerCase();
                                    let badgeClass = 'bg-secondary';
                                    if (situacao.includes('em dia')) badgeClass = 'bg-success';
                                    else if (situacao.includes('atraso')) badgeClass = 'bg-warning text-dark';
                                    else if (situacao.includes('cancelad')) badgeClass = 'bg-danger';
                                    return `<span class="badge ${badgeClass}">${data}</span>`;
                                } return data;
                            }},
                            { targets: numericColumnIndexes, className: 'text-end' },
                            { targets: actionsColumnIndex, className: 'text-center' }
                        ],
                        // ---- CONFIGURAÇÃO GERAL E BOTÕES DE EXPORTAÇÃO ----
                        responsive: true, 
                        pageLength: 10,
                        dom: "<'row mb-3'<'col-md-6'l><'col-md-6 text-end'B>>" +
                             "<'row'<'col-sm-12'tr>>" +
                             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                        buttons: [
                            {
                                extend: 'excelHtml5',
                                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                                className: 'btn btn-success btn-sm',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5, 6, 7] // Exporta tudo, menos a coluna 8 (Ações)
                                }
                            },
                            {
                                extend: 'csvHtml5',
                                text: '<i class="bi bi-filetype-csv"></i> CSV',
                                className: 'btn btn-info btn-sm text-white',
                                exportOptions: {
                                    columns: exportColumnIndexes
                                }
                            },
                            {
                                extend: 'pdfHtml5',
                                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                                className: 'btn btn-danger btn-sm',
                                orientation: 'landscape',
                                exportOptions: {
                                    columns: exportColumnIndexes
                                }
                            }
                        ],
                        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json" },
                        order: [[isOperador ? 0 : 1,'desc']]
                    });
                } else {
                    table.clear().rows.add(resp.data || []).draw();
                }
            } catch (error) {
                console.error("Falha ao carregar os dados da tabela:", error);
            } finally {
                loader.style.display = 'none';
            }
        }

        document.getElementById('btnAplicar').addEventListener('click', carregarTabela);
        document.getElementById('btnLimpar').addEventListener('click', () => {
            document.querySelectorAll('input.form-control, select.form-select').forEach(el => el.value = '');
            document.getElementById('filtroStatus').value = 'todos';
            const filtroInativosToggle = document.getElementById('filtroIncluirInativos');
            if (filtroInativosToggle) filtroInativosToggle.checked = false;
            carregarTabela();
        });
        document.getElementById('filtroBusca').addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); carregarTabela(); } });
        const filtroInativosToggle = document.getElementById('filtroIncluirInativos');
        if (filtroInativosToggle) filtroInativosToggle.addEventListener('change', carregarTabela);

        window.addEventListener('load', carregarTabela);

        const clientDetailsModal = document.getElementById('clientDetailsModal');
            if(clientDetailsModal) {
                clientDetailsModal.addEventListener('show.bs.modal', async function (event) {
                    const modalLoader = document.getElementById('modal-loader');
                    const modalContent = document.getElementById('modal-content-details');
                    
                    // Reseta visualização
                    modalLoader.style.display = 'block';
                    modalContent.style.display = 'none';
                    
                    const button = event.relatedTarget;
                    const rowId = button.getAttribute('data-row-id');
        
                    // Prevenção se o ID vier vazio
                    if (!rowId || rowId === "undefined") {
                        modalLoader.style.display = 'none';
                        modalContent.innerHTML = '<p class="text-danger">Erro: ID do cliente não identificado.</p>';
                        modalContent.style.display = 'block';
                        return;
                    }
        
                    // Adiciona timestamp para evitar cache do navegador
                    const response = await fetchDados({ id: rowId, _: new Date().getTime() });
        
                    if (response && response.data) {
                        // TRATAMENTO DE ARRAY VS OBJETO
                        const d = Array.isArray(response.data) ? response.data[0] : response.data;
                        
                        if (d) {
                            document.getElementById('modal-nome_cliente').textContent = formatText(d.nome_cliente);
                            document.getElementById('modal-numero_contrato').textContent = formatText(d.numero_contrato);
                            document.getElementById('modal-telefone_cliente').textContent = formatText(d.telefone_cliente);
                            document.getElementById('modal-data_venda').textContent = formatText(d.data_venda);
                            document.getElementById('modal-valor_cota').textContent = formatBRL(d.valor_cota);
                            document.getElementById('modal-parcelas_pagas_atual').textContent = formatText(d.parcelas_pagas_atual);
                            document.getElementById('modal-status_atual').textContent = formatText(d.status_atual);
                            if (!isOperador) {
                                document.getElementById('modal-nome_consultor').textContent = formatText(d.nome_consultor);
                                document.getElementById('modal-email_consultor').textContent = formatText(d.email_consultor);
                                document.getElementById('modal-equipe').textContent = formatText(d.equipe);
                                
                                const statusConsultor = formatText(d.status_consultor);
                                const statusEl = document.getElementById('modal-status_consultor');
                                let badgeClass = String(statusConsultor).toLowerCase() === 'inativo' ? 'bg-danger' : 'bg-success';
                                statusEl.innerHTML = `<span class="badge ${badgeClass}">${statusConsultor || 'Ativo'}</span>`;
                            }
                            
                            // Sucesso: mostra o grid
                            modalLoader.style.display = 'none';
                            modalContent.style.display = 'grid';
                        } else {
                            modalLoader.style.display = 'none';
                            modalContent.innerHTML = '<p class="text-warning">Dados vazios retornados pelo servidor.</p>';
                            modalContent.style.display = 'block';
                        }
                    } else {
                        modalLoader.style.display = 'none';
                        modalContent.innerHTML = '<p class="text-danger">Não foi possível carregar os detalhes do cliente.</p>';
                        modalContent.style.display = 'block';
                    }
                });
            }
            
    </script><script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnSincronizar = document.getElementById('btnSincronizarPlanilha');
            
            if (btnSincronizar) {
                btnSincronizar.addEventListener('click', async function() {
                    // Guarda o texto original e coloca o ícone girando
                    const conteudoOriginal = this.innerHTML;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Rodando...';
                    this.disabled = true;
        
                    try {
                        const response = await fetch('sincronizar_planilha.php', { 
                            method: 'POST' 
                        });
                        
                        const data = await response.json();
        
                        if (response.ok && data.sucesso) {
                            alert('Sincronização concluída com sucesso!' + (data.mensagem ? "\n" + data.mensagem : ''));
                        } else {
                            alert('Aviso do robô: ' + (data.mensagem || 'Erro desconhecido.'));
                        }
                    } catch (error) {
                        console.error("Erro na requisição:", error);
                        alert('Falha ao tentar iniciar o robô. Verifique a conexão.');
                    } finally {
                        // Devolve o botão ao estado normal
                        this.innerHTML = conteudoOriginal;
                        this.disabled = false;
                    }
                });
            }
        });
    </script>
</body>
</html>
