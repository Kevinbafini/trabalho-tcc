/**
 * Gráficos do sistema (Chart.js), usados como componentes Alpine.js:
 *
 *   <canvas x-data="graficoComposicao(dados)"></canvas>
 *   <canvas x-data="graficoCustoPorHectare(dados)"></canvas>
 *
 * Os dados chegam prontos do servidor: { rotulos: [...], valores: [...] }.
 */
import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const moedaCompacta = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact' });
const percentual = new Intl.NumberFormat('pt-BR', { style: 'percent', maximumFractionDigits: 1 });

/**
 * Plugin simples que escreve o valor de cada barra logo após a ponta dela,
 * para que o número possa ser lido sem passar o mouse.
 */
const rotulosNasBarras = {
    id: 'rotulosNasBarras',
    afterDatasetsDraw(grafico, argumentos, opcoes) {
        const { ctx } = grafico;

        ctx.save();
        ctx.font = '12px "Instrument Sans", ui-sans-serif, system-ui, sans-serif';
        ctx.fillStyle = opcoes.cor;
        ctx.textBaseline = 'middle';

        grafico.getDatasetMeta(0).data.forEach((barra, indice) => {
            ctx.fillText(moeda.format(grafico.data.datasets[0].data[indice]), barra.x + 6, barra.y);
        });

        ctx.restore();
    },
};

/**
 * Cores por tema. As três cores das séries foram validadas para daltonismo
 * contra a superfície dos cards (branca no tema claro, #3c3c3c no escuro).
 */
function cores() {
    const escuro = document.documentElement.classList.contains('dark');

    return escuro
        ? { series: ['#3987e5', '#d95926', '#199e70'], superficie: '#3c3c3c', texto: '#d4d4d4', grade: 'rgba(255, 255, 255, 0.08)' }
        : { series: ['#2a78d6', '#eb6834', '#1baf7a'], superficie: '#ffffff', texto: '#525252', grade: 'rgba(0, 0, 0, 0.06)' };
}

function opcoesDeEixo(paleta) {
    return {
        ticks: { color: paleta.texto },
        grid: { color: paleta.grade },
        border: { display: false },
    };
}

/**
 * Composição do custo (Insumos × Maquinário × Mão de obra) em uma única
 * barra horizontal empilhada. Cada parte mostra valor e percentual no tooltip;
 * a legenda com os valores fica no HTML, ao lado do gráfico.
 */
function criarGraficoComposicao(canvas, dados) {
    const paleta = cores();
    const total = dados.valores.reduce((soma, valor) => soma + valor, 0);
    const ultimo = dados.valores.length - 1;

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['Composição'],
            datasets: dados.rotulos.map((rotulo, indice) => ({
                label: rotulo,
                data: [dados.valores[indice]],
                backgroundColor: paleta.series[indice],
                borderColor: paleta.superficie,
                borderWidth: { left: indice === 0 ? 0 : 2, right: 0, top: 0, bottom: 0 },
                borderSkipped: false,
                borderRadius: {
                    topLeft: indice === 0 ? 4 : 0,
                    bottomLeft: indice === 0 ? 4 : 0,
                    topRight: indice === ultimo ? 4 : 0,
                    bottomRight: indice === ultimo ? 4 : 0,
                },
                barThickness: 28,
            })),
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: {
                x: { stacked: true, display: false, max: total || 1 },
                y: { stacked: true, display: false },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: () => '',
                        label: (contexto) => {
                            const valor = contexto.parsed.x;
                            const parte = total > 0 ? valor / total : 0;

                            return `${contexto.dataset.label}: ${moeda.format(valor)} (${percentual.format(parte)})`;
                        },
                    },
                },
            },
        },
    });
}

/**
 * Custo por hectare de cada talhão, em barras horizontais (uma cor só,
 * pois há uma única série). Ordenado do maior para o menor custo.
 */
function criarGraficoCustoPorHectare(canvas, dados) {
    const paleta = cores();
    const pares = dados.rotulos
        .map((rotulo, indice) => ({ rotulo, valor: dados.valores[indice] }))
        .sort((a, b) => b.valor - a.valor);

    return new Chart(canvas, {
        type: 'bar',
        plugins: [rotulosNasBarras],
        data: {
            labels: pares.map((par) => par.rotulo),
            datasets: [
                {
                    label: 'Custo por hectare',
                    data: pares.map((par) => par.valor),
                    backgroundColor: paleta.series[0],
                    borderRadius: 4,
                    borderSkipped: 'start',
                    maxBarThickness: 24,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            layout: { padding: { right: 96 } },
            scales: {
                x: {
                    ...opcoesDeEixo(paleta),
                    beginAtZero: true,
                    ticks: { color: paleta.texto, maxTicksLimit: 5, callback: (valor) => moedaCompacta.format(valor) },
                },
                y: { ...opcoesDeEixo(paleta), grid: { display: false } },
            },
            plugins: {
                legend: { display: false },
                rotulosNasBarras: { cor: paleta.texto },
                tooltip: {
                    callbacks: {
                        label: (contexto) => `${moeda.format(contexto.parsed.x)} por hectare`,
                    },
                },
            },
        },
    });
}

/**
 * Componentes Alpine. A instância do gráfico fica fora do estado reativo
 * do Alpine (variável local), como recomenda a documentação do Chart.js.
 */
window.graficoComposicao = (dados) => {
    let grafico = null;

    return {
        init() {
            grafico = criarGraficoComposicao(this.$el, dados);
        },
        destroy() {
            grafico?.destroy();
        },
    };
};

window.graficoCustoPorHectare = (dados) => {
    let grafico = null;

    return {
        init() {
            grafico = criarGraficoCustoPorHectare(this.$el, dados);
        },
        destroy() {
            grafico?.destroy();
        },
    };
};
