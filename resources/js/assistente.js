/**
 * Assistente IA (componentes Alpine.js).
 *
 *   chatDoAssistente()   envia a mensagem em segundo plano e troca a lista de
 *                        mensagens pelo HTML que o servidor devolve já pronto
 *                        (veja resources/views/assistente/partials/chat.blade.php);
 *   painelDoAssistente() carrega o chat no painel lateral quando ele é aberto.
 *
 * Sem JavaScript, o formulário do chat continua funcionando com envio normal.
 */
window.chatDoAssistente = ({ conversaId = null, provedor, restantes = 0, atualizarEndereco = false }) => ({
    conversaId,
    provedor,
    restantes,
    mensagem: '',
    pendente: '',
    enviando: false,
    erro: null,

    init() {
        this.$nextTick(() => this.rolarParaOFim());
    },

    /**
     * Envia a mensagem e mostra a resposta da IA.
     */
    async enviar() {
        const texto = this.mensagem.trim();

        if (texto === '' || this.enviando) {
            return;
        }

        const dados = new FormData(this.$refs.formulario);
        dados.set('mensagem', texto);
        dados.set('provedor', this.provedor);
        dados.set('conversa_id', this.conversaId ?? '');

        this.erro = null;
        this.pendente = texto;
        this.mensagem = '';
        this.enviando = true;
        this.$nextTick(() => {
            this.ajustarAltura();
            this.rolarParaOFim();
        });

        try {
            const resposta = await fetch(this.$refs.formulario.action, {
                method: 'POST',
                body: dados,
                headers: { Accept: 'application/json' },
            });
            const json = await resposta.json().catch(() => null);

            if (resposta.status === 422) {
                this.erro = Object.values(json?.errors ?? {})[0]?.[0] ?? 'Confira a mensagem e tente de novo.';
                this.mensagem = texto;

                return;
            }

            if (!resposta.ok || json === null) {
                this.erro = resposta.status === 419
                    ? 'Sua sessão expirou. Recarregue a página e tente de novo.'
                    : 'Não foi possível enviar a mensagem. Tente de novo em alguns instantes.';
                this.mensagem = texto;

                return;
            }

            this.conversaId = json.conversa_id;
            this.restantes = json.restantes;
            this.erro = json.erro;
            this.$refs.mensagens.innerHTML = json.mensagens;

            if (json.historico !== null) {
                window.dispatchEvent(new CustomEvent('assistente-historico', { detail: { html: json.historico } }));
            }

            if (atualizarEndereco && json.url) {
                window.history.replaceState(window.history.state, '', json.url);
            }
        } catch {
            this.erro = 'Sem conexão com o servidor. Verifique a internet e tente de novo.';
            this.mensagem = texto;
        } finally {
            this.enviando = false;
            this.pendente = '';
            this.$nextTick(() => {
                this.ajustarAltura();
                this.rolarParaOFim();
                this.$refs.campo?.focus();
            });
        }
    },

    /**
     * Sugestão clicada no início da conversa: envia direto.
     */
    usarSugestao(texto) {
        this.mensagem = texto;
        this.enviar();
    },

    /**
     * Enter envia; Shift + Enter quebra a linha.
     */
    teclaEnter(evento) {
        if (evento.shiftKey || evento.isComposing) {
            return;
        }

        evento.preventDefault();
        this.enviar();
    },

    /**
     * O campo cresce com o texto (até o limite definido no CSS).
     */
    ajustarAltura() {
        const campo = this.$refs.campo;

        if (!campo) {
            return;
        }

        campo.style.height = 'auto';
        campo.style.height = `${campo.scrollHeight}px`;
    },

    rolarParaOFim() {
        const rolagem = this.$refs.rolagem;

        if (rolagem) {
            rolagem.scrollTop = rolagem.scrollHeight;
        }
    },
});

window.painelDoAssistente = (url) => ({
    /**
     * Carrega o chat no painel: a conversa mais recente, ou uma nova ({ nova: 1 }).
     */
    async carregar(parametros = {}) {
        const consulta = new URLSearchParams(parametros).toString();

        try {
            const resposta = await fetch(consulta ? `${url}?${consulta}` : url, { headers: { Accept: 'text/html' } });

            if (!resposta.ok || resposta.redirected) {
                throw new Error(String(resposta.status));
            }

            this.$refs.conteudo.innerHTML = await resposta.text();
        } catch {
            this.$refs.conteudo.innerHTML = '<p class="m-auto p-6 text-center text-sm text-zinc-500">Não foi possível abrir o assistente. Recarregue a página e tente de novo.</p>';
        }
    },
});
