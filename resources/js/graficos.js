/**
 * Gráficos do sistema (Chart.js), usados como componentes Alpine.js:
 *
 *   <canvas x-data="graficoComposicao(dados)"></canvas>        barra única: insumos × maquinário × mão de obra
 *   <canvas x-data="graficoBarras(dados)"></canvas>            barras horizontais de uma série (ordenadas)
 *   <canvas x-data="graficoCustoPorHectare(dados)"></canvas>   igual a graficoBarras, com "por hectare" no tooltip
 *   <canvas x-data="graficoBarrasEmpilhadas(dados)"></canvas>  barras empilhadas pelos três grupos de custo
 *
 * Os dados chegam prontos do servidor (GraficosCustoService):
 *   { rotulos: [...], valores: [...] }                         séries simples
 *   { rotulos: [...], series: [{ rotulo, valores }], orientacao: 'horizontal' | 'vertical' }
 */
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

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
 * Cores por tema. As três cores das séries (insumos, maquinário, mão de obra)
 * foram validadas para daltonismo contra a superfície dos cards. A superfície
 * e o texto seguem os cinzas esverdeados do tema (resources/css/app.css).
 */
function cores() {
    const escuro = document.documentElement.classList.contains('dark');

    return escuro
        ? { series: ['#3987e5', '#d95926', '#199e70'], superficie: '#313f37', texto: '#ccd8d0', grade: 'rgba(255, 255, 255, 0.08)' }
        : { series: ['#2a78d6', '#eb6834', '#1baf7a'], superficie: '#ffffff', texto: '#48564d', grade: 'rgba(10, 78, 53, 0.08)' };
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
 * barra horizontal empilhada. A legenda com os valores fica no HTML.
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
 * Barras horizontais de uma única série (uma cor só), ordenadas do maior
 * para o menor, com o valor escrito na ponta de cada barra.
 * Opcional: dados.sufixo, texto exibido após o valor no tooltip (ex.: "por hectare").
 */
function criarGraficoBarrasHorizontais(canvas, dados) {
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
                    label: dados.sufixo ?? 'Custo',
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
                        label: (contexto) => `${moeda.format(contexto.parsed.x)}${dados.sufixo ? ` ${dados.sufixo}` : ''}`,
                    },
                },
            },
        },
    });
}

/**
 * Barras empilhadas pelos três grupos de custo (insumos, maquinário e mão de obra).
 * dados.orientacao: 'horizontal' (padrão, ex.: custo por talhão) ou 'vertical' (ex.: evolução mensal).
 * Um espaço de 2px na cor do card separa os segmentos; o tooltip mostra os três valores e o total.
 */
function criarGraficoBarrasEmpilhadas(canvas, dados) {
    const paleta = cores();
    const horizontal = dados.orientacao !== 'vertical';
    const eixoDoValor = horizontal ? 'x' : 'y';
    const eixoDaCategoria = horizontal ? 'y' : 'x';
    const valorDoItem = (item) => (horizontal ? item.parsed.x : item.parsed.y);

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: dados.rotulos,
            datasets: dados.series.map((serie, indice) => ({
                label: serie.rotulo,
                data: serie.valores,
                backgroundColor: paleta.series[indice],
                borderColor: paleta.superficie,
                borderWidth: indice === 0 ? 0 : horizontal ? { left: 2 } : { bottom: 2 },
                borderSkipped: false,
                maxBarThickness: horizontal ? 22 : 40,
            })),
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                [eixoDoValor]: {
                    ...opcoesDeEixo(paleta),
                    stacked: true,
                    beginAtZero: true,
                    ticks: { color: paleta.texto, maxTicksLimit: 6, callback: (valor) => moedaCompacta.format(valor) },
                },
                [eixoDaCategoria]: { ...opcoesDeEixo(paleta), stacked: true, grid: { display: false } },
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: paleta.texto, usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, padding: 16 },
                },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.dataset.label}: ${moeda.format(valorDoItem(item))}`,
                        footer: (itens) => `Total: ${moeda.format(itens.reduce((soma, item) => soma + valorDoItem(item), 0))}`,
                    },
                },
            },
        },
    });
}

/**
 * Transforma uma função de criação de gráfico em um componente Alpine.
 * A instância do gráfico fica fora do estado reativo do Alpine (variável local),
 * como recomenda a documentação do Chart.js.
 */
function componenteDeGrafico(criar) {
    return (dados) => {
        let grafico = null;

        return {
            init() {
                grafico = criar(this.$el, dados);
            },
            destroy() {
                grafico?.destroy();
            },
        };
    };
}

window.graficoComposicao = componenteDeGrafico(criarGraficoComposicao);
window.graficoBarras = componenteDeGrafico(criarGraficoBarrasHorizontais);
window.graficoCustoPorHectare = componenteDeGrafico((canvas, dados) => criarGraficoBarrasHorizontais(canvas, { sufixo: 'por hectare', ...dados }));
window.graficoBarrasEmpilhadas = componenteDeGrafico(criarGraficoBarrasEmpilhadas);
