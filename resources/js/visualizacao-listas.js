/**
 * Modo de visualização das listas no computador: "tabela" ou "cartoes".
 *
 * A escolha vale para todas as listas e fica salva no navegador
 * (localStorage). Em telas menores que 1280px os cards são sempre usados,
 * então o modo só faz diferença em telas grandes.
 *
 * Uso (componente Alpine.js):
 *   <div x-data="visualizacaoDasListas()" x-on:visualizacao-alterada.window="modo = $event.detail">
 */
const CHAVE = 'visualizacao-das-listas';

function modoSalvo() {
    try {
        return localStorage.getItem(CHAVE) === 'cartoes' ? 'cartoes' : 'tabela';
    } catch {
        // Navegador sem acesso ao localStorage (ex.: modo privado restrito).
        return 'tabela';
    }
}

window.visualizacaoDasListas = () => ({
    modo: modoSalvo(),

    /**
     * Troca o modo, salva a escolha e avisa as outras listas da página.
     */
    alterar(modo) {
        try {
            localStorage.setItem(CHAVE, modo);
        } catch {
            // Sem localStorage a escolha vale apenas até recarregar a página.
        }

        window.dispatchEvent(new CustomEvent('visualizacao-alterada', { detail: modo }));
    },
});
