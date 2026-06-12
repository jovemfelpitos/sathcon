// Aguarda o DOM estar completamente carregado
document.addEventListener('DOMContentLoaded', function () {

    // Formata números para BRL
    function brFormat(num) {
        try {
            return Number(num).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        } catch (e) {
            return num;
        }
    }

    // Inicializa os gráficos
    function initCharts() {
        // Gráfico Mensal de Barras
        const elMonths = document.getElementById('chartMonths');
        if (elMonths && typeof Chart !== 'undefined' && window.chartData) {
            new Chart(elMonths.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: window.chartData.monthsLabels,
                    datasets: [{
                        label: 'Comissão (R$)',
                        data: window.chartData.monthsData,
                        backgroundColor: window.chartData.monthsData.map((v, i) =>
                            i === window.chartData.highlightIndex ? '#ffd54a' : 'rgba(255, 213, 74, 0.7)'
                        ),
                        borderColor: '#ffd54a',
                        borderWidth: window.chartData.monthsData.map((v, i) =>
                            i === window.chartData.highlightIndex ? 1 : 0
                        ),
                        borderRadius: 6,
                        maxBarThickness: 44
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => brFormat(context.raw)
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: value => `R$ ${Number(value/1000).toFixed(0)}k`
                            },
                            grid: { color: 'rgba(255, 255, 255, 0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Gráfico de Pizza (Doughnut)
        const elPie = document.getElementById('chartPie');
        if (elPie && typeof Chart !== 'undefined' && window.chartData) {
            new Chart(elPie.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Em dia', 'Em atraso', 'Cancelado'],
                    datasets: [{
                        data: window.chartData.pieData.map(v => Number(v || 0)),
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: 'var(--muted)' }
                        },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.label}: ${brFormat(context.raw)}`
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Inicializa os selects com Choices.js
    document.querySelectorAll('.choices-select').forEach(el => {
        new Choices(el, {
            searchEnabled: true,
            removeItemButton: true,
            placeholderValue: 'Selecione uma opção',
            itemSelectText: 'Pressione para selecionar'
        });
    });

    // Inicia os gráficos
    initCharts();
    
    // Mostra o conteúdo e esconde o loader
    const loader = document.getElementById('loader');
    const content = document.querySelector('.comissao-root');
    
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 500);
    }
    if (content) {
        content.classList.add('loaded');
    }
});