// ARQUIVO: assets/js/app.js - VERSÃO FINAL E COMPLETA

$(document).ready(function() {
    let table = null;
    let filtrosPopulares = false;

    // --- ELEMENTOS DO DOM ---
    const filtroPeriodo = document.getElementById('filtroPeriodo');
    const containerDataPersonalizada = document.getElementById('containerDataPersonalizada');
    const filtroDataDe = document.getElementById('filtroDataDe');
    const filtroDataAte = document.getElementById('filtroDataAte');

    // --- FUNÇÕES AUXILIARES ---
    const formatBRL = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(v) || 0);
    const formatText = (v) => v || 'N/A';
    const formatDate = (date) => date.toISOString().split('T')[0];

    function criarBadgeStatus(statusTexto) {
        const statusLower = String(statusTexto || '').toLowerCase();
        let badgeClass = 'bg-secondary';
        if (statusLower.includes('em dia')) badgeClass = 'bg-success';
        else if (statusLower.includes('atraso')) badgeClass = 'bg-warning text-dark';
        else if (statusLower.includes('cancelad')) badgeClass = 'bg-danger';
        return `<span class="badge ${badgeClass}">${statusTexto || 'N/A'}</span>`;
    }

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
        const consultores = [...new Set(dadosCompletos.map(item => item.nome_consultor))].filter(Boolean).sort();
        const filtroConsultor = document.getElementById('filtroConsultor');
        let optionsHtml = '<option value="">Todos</option>';
        consultores.forEach(c => {
            optionsHtml += `<option value="${c}">${c}</option>`;
        });
        filtroConsultor.innerHTML = optionsHtml;
    }

    // --- LÓGICA DO NOVO FILTRO DE DATA ---
    function atualizarDatasPeloPeriodo() {
        const hoje = new Date();
        let dataInicio = new Date();
        let dataFim = new Date();
        const ano = hoje.getFullYear();
        const mes = hoje.getMonth();

        switch (filtroPeriodo.value) {
            case 'hoje':
                break;
            case 'ultimos_7_dias':
                dataInicio.setDate(hoje.getDate() - 6);
                break;
            case 'ultimos_30_dias':
                dataInicio.setDate(hoje.getDate() - 29);
                break;
            case 'este_mes':
                dataInicio = new Date(ano, mes, 1);
                dataFim = new Date(ano, mes + 1, 0);
                break;
            case 'mes_passado':
                dataInicio = new Date(ano, mes - 1, 1);
                dataFim = new Date(ano, mes, 0);
                break;
            case 'personalizado':
                containerDataPersonalizada.style.display = 'flex';
                return;
        }
        containerDataPersonalizada.style.display = 'none';
        filtroDataDe.value = formatDate(dataInicio);
        filtroDataAte.value = formatDate(dataFim);
    }

    async function carregarTabela() {
        const loader = document.getElementById('table-loader-overlay');
        loader.style.display = 'flex';
        const filtroInativosToggle = document.getElementById('filtroIncluirInativos');
        const params = {
            status: document.getElementById('filtroStatus').value,
            consultor: document.getElementById('filtroConsultor').value,
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
            if (!table) {
                table = $('#tabelaClientes').DataTable({
                    data: resp.data || [],
                    columns: [
                        { data: 'id', visible: false },
                        { data: 'data_venda' },
                        { data: 'nome_consultor', render: (data, type, row) => (row.status_consultor === 'inativo') ? `<span class="text-inactive" title="Consultor Inativo">${data}</span>` : data },
                        { data: 'numero_contrato' },
                        { data: 'parcelas_pagas_atual' },
                        { data: 'valor_cota', render: val => formatBRL(val) },
                        { data: 'status_atual' },
                        { data: 'nome_cliente' },
                        { data: 'telefone_cliente' },
                        { data: null, orderable: false, searchable: false, render: (data,type,row) => buildActions(row) }
                    ],
                    columnDefs: [
                        { targets: 6, render: (data) => criarBadgeStatus(data) },
                        { targets: [4, 5], className: 'text-end' },
                        { targets: 9, className: 'text-center' }
                    ],
                    responsive: true, pageLength: 10,
                    dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json" },
                    order: [[1,'desc']]
                });
            } else {
                table.clear().rows.add(resp.data || []).draw();
            }
        } catch(error) {
            console.error("Falha ao carregar a tabela: ", error);
        } finally {
            loader.style.display = 'none';
        }
    }

    // --- Event Listeners ---
    filtroPeriodo.addEventListener('change', atualizarDatasPeloPeriodo);
    document.getElementById('btnAplicar').addEventListener('click', carregarTabela);
    document.getElementById('btnLimpar').addEventListener('click', () => {
        document.querySelectorAll('input.form-control, select.form-select').forEach(el => {
            if (el.id !== 'filtroPeriodo') el.value = '';
        });
        document.getElementById('filtroStatus').value = 'todos';
        filtroPeriodo.value = 'este_mes';
        const filtroInativosToggle = document.getElementById('filtroIncluirInativos');
        if (filtroInativosToggle) filtroInativosToggle.checked = false;
        atualizarDatasPeloPeriodo();
        carregarTabela();
    });
    document.getElementById('filtroBusca').addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); carregarTabela(); } });
    const filtroInativosToggle = document.getElementById('filtroIncluirInativos');
    if (filtroInativosToggle) filtroInativosToggle.addEventListener('change', carregarTabela);
    
    // Carga Inicial
    atualizarDatasPeloPeriodo();
    carregarTabela();

    // Lógica do Modal
    const clientDetailsModal = document.getElementById('clientDetailsModal');
    if(clientDetailsModal) {
        clientDetailsModal.addEventListener('show.bs.modal', async function (event) {
            const modalLoader = document.getElementById('modal-loader');
            const modalContent = document.getElementById('modal-content-details');
            modalLoader.style.display = 'block';
            modalContent.style.display = 'none';
            const button = event.relatedTarget;
            const rowId = button.getAttribute('data-row-id');
            const response = await fetchDados({ id: rowId });
            if (response && response.data) {
                const d = response.data;
                document.getElementById('modal-nome_cliente').textContent = formatText(d.nome_cliente);
                document.getElementById('modal-numero_contrato').textContent = formatText(d.numero_contrato);
                document.getElementById('modal-telefone_cliente').textContent = formatText(d.telefone_cliente);
                document.getElementById('modal-data_venda').textContent = formatText(d.data_venda);
                document.getElementById('modal-valor_cota').textContent = formatBRL(d.valor_cota);
                document.getElementById('modal-parcelas_pagas_atual').textContent = formatText(d.parcelas_pagas_atual);
                document.getElementById('modal-status_atual').innerHTML = criarBadgeStatus(d.status_atual);
                document.getElementById('modal-nome_consultor').textContent = formatText(d.nome_consultor);
                document.getElementById('modal-email_consultor').textContent = formatText(d.email_consultor);
                document.getElementById('modal-equipe').textContent = formatText(d.equipe);
                document.getElementById('modal-comissao_planilha').textContent = formatBRL(d.comissao_planilha);
                const statusConsultor = formatText(d.status_consultor);
                const statusEl = document.getElementById('modal-status_consultor');
                let badgeClass = statusConsultor.toLowerCase() === 'inativo' ? 'bg-danger' : 'bg-success';
                statusEl.innerHTML = `<span class="badge ${badgeClass}">${statusConsultor || 'Ativo'}</span>`;
            } else {
                modalContent.innerHTML = '<p class="text-danger">Não foi possível carregar os detalhes do cliente.</p>';
            }
            modalLoader.style.display = 'none';
            modalContent.style.display = 'grid';
        });
    }
});