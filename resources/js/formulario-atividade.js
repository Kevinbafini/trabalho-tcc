/**
 * Formulário de atividade agrícola (componente Alpine.js).
 *
 * Permite adicionar e remover insumos, máquinas e funcionários e mostra uma
 * PRÉVIA do custo enquanto o usuário preenche. O cálculo definitivo é sempre
 * refeito no servidor pelo CustoAtividadeService ao salvar.
 *
 * Dados recebidos do servidor:
 *   opcoes:    recursos disponíveis por tipo ({ id, nome, unidade, valor })
 *   historico: valores gravados na atividade em edição, por tipo ({ id: valor })
 *   linhas:    linhas já preenchidas, por tipo ({ recurso, quantidade })
 *   erros:     mensagens de validação do servidor ({ campo: [mensagens] })
 *   urlCadastro: endereço do formulário de cadastro (usado ao trocar de propriedade)
 */
const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

window.formularioAtividade = (dados) => ({
    opcoes: dados.opcoes,
    historico: dados.historico,
    linhas: dados.linhas,
    erros: dados.erros,
    urlCadastro: dados.urlCadastro,
    proximaChave: 1,

    init() {
        // Cada linha recebe uma chave única para o Alpine controlar a lista (x-for).
        for (const tipo of Object.keys(this.linhas)) {
            this.linhas[tipo] = this.linhas[tipo].map((linha) => ({ ...linha, chave: this.proximaChave++ }));
        }
    },

    /**
     * Recarrega o formulário com os talhões e recursos da propriedade escolhida.
     */
    trocarPropriedade(propriedadeId) {
        window.location = `${this.urlCadastro}?propriedade_id=${propriedadeId}`;
    },

    adicionar(tipo) {
        this.linhas[tipo].push({ recurso: '', quantidade: '', chave: this.proximaChave++ });
    },

    remover(tipo, indice) {
        this.linhas[tipo].splice(indice, 1);
    },

    opcao(tipo, id) {
        return this.opcoes[tipo].find((opcao) => String(opcao.id) === String(id));
    },

    /**
     * Itens que já pertenciam à atividade usam o valor gravado no lançamento;
     * itens novos usam o valor atual do cadastro (mesma regra do servidor).
     */
    usaValorHistorico(tipo, id) {
        return id !== '' && this.historico[tipo]?.[id] !== undefined;
    },

    valorUnitario(tipo, id) {
        if (!id) {
            return 0;
        }

        if (this.usaValorHistorico(tipo, id)) {
            return Number(this.historico[tipo][id]);
        }

        return Number(this.opcao(tipo, id)?.valor ?? 0);
    },

    custoDaLinha(tipo, linha) {
        const quantidade = Number(String(linha.quantidade).replace(',', '.')) || 0;

        return Math.round(quantidade * this.valorUnitario(tipo, linha.recurso) * 100) / 100;
    },

    subtotal(tipo) {
        return this.linhas[tipo].reduce((soma, linha) => soma + this.custoDaLinha(tipo, linha), 0);
    },

    get total() {
        return this.subtotal('insumos') + this.subtotal('maquinas') + this.subtotal('funcionarios');
    },

    /**
     * Texto auxiliar da linha, ex.: "R$ 3,20/kg" ou "R$ 180,00/h (valor do lançamento)".
     */
    descricaoDoValor(tipo, linha) {
        if (!linha.recurso) {
            return 'Selecione para ver o valor';
        }

        const unidade = this.opcao(tipo, linha.recurso)?.unidade ?? '';
        const texto = `${moeda.format(this.valorUnitario(tipo, linha.recurso))}/${unidade}`;

        return this.usaValorHistorico(tipo, linha.recurso) ? `${texto} (valor do lançamento)` : texto;
    },

    unidade(tipo, linha) {
        return this.opcao(tipo, linha.recurso)?.unidade ?? '';
    },

    moeda(valor) {
        return moeda.format(valor);
    },

    erro(campo) {
        return this.erros[campo]?.[0] ?? '';
    },
});
