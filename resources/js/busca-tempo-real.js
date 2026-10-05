/**
 * Busca em tempo real das listagens (componente Alpine.js).
 *
 * Enquanto o usuário digita ou troca um filtro, a busca é enviada ao servidor
 * e somente o trecho da lista é substituído na página, sem recarregar e sem
 * perder o foco do campo. Ao receber o cabeçalho X-Busca-Em-Tempo-Real, o
 * controller devolve apenas o @fragment('resultados') da view.
 *
 * Uso (veja o componente <x-busca>):
 *   <div x-data="buscaEmTempoReal()">
 *       <form x-ref="formulario"> ...campos e filtros... </form>
 *       <div x-ref="resultados"> ...lista... </div>
 *   </div>
 */
window.buscaEmTempoReal = () => {
    // Fica fora do estado reativo do Alpine: só serve para cancelar a busca anterior.
    let requisicaoAnterior = null;

    return {
        carregando: false,

        /**
         * Envia os filtros preenchidos e troca a lista pelos resultados.
         */
        async buscar() {
            const parametros = new URLSearchParams();

            for (const [campo, valor] of new FormData(this.$refs.formulario)) {
                if (String(valor).trim() !== '') {
                    parametros.append(campo, valor);
                }
            }

            const consulta = parametros.toString();
            const url = consulta ? `${window.location.pathname}?${consulta}` : window.location.pathname;

            requisicaoAnterior?.abort();
            requisicaoAnterior = new AbortController();
            this.carregando = true;

            try {
                const resposta = await fetch(url, {
                    headers: { 'X-Busca-Em-Tempo-Real': '1' },
                    signal: requisicaoAnterior.signal,
                });

                // Sessão expirada ou erro: abre a página normalmente.
                if (!resposta.ok || resposta.redirected) {
                    window.location.href = url;

                    return;
                }

                this.$refs.resultados.innerHTML = await resposta.text();
                window.history.replaceState(window.history.state, '', url);
                this.carregando = false;
            } catch (erro) {
                if (erro.name !== 'AbortError') {
                    window.location.href = url;
                }
            }
        },

        /**
         * Limpa a busca e todos os filtros.
         */
        limpar() {
            this.$refs.formulario.querySelectorAll('input, select').forEach((campo) => {
                campo.value = '';
            });

            this.buscar();
        },
    };
};
