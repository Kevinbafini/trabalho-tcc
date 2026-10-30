<?php

/*
|--------------------------------------------------------------------------
| Ajuda das telas
|--------------------------------------------------------------------------
|
| Textos do botão "?" exibido ao lado do título de cada tela. Cada entrada é
| identificada pelo nome da rota da tela (ex.: "propriedades.index") e tem:
|
|   titulo     nome da tela no topo da janela de ajuda
|   proposito  para que serve a tela (um parágrafo)
|   exibe      o que é exibido (lista)
|   como_usar  passos ou ações disponíveis (lista)
|   dica       observação importante (opcional)
|
| Trechos entre **asteriscos** aparecem em negrito.
|
*/

return [

    'dashboard' => [
        'titulo' => 'Dashboard',
        'proposito' => 'É a página inicial do sistema. Reúne os principais números das suas propriedades para você acompanhar, em poucos segundos, quanto já foi gasto e em quais talhões.',
        'exibe' => [
            '**Indicadores**: área total, quantidade de talhões, atividades registradas, custo total e custo médio por hectare.',
            '**Composição dos custos**: quanto do total foi gasto com insumos, maquinário e mão de obra.',
            '**Custo por hectare dos talhões**: gráfico que compara os talhões pelo custo de cada hectare.',
            '**Últimas atividades** registradas, com data, talhão, operação e custo.',
        ],
        'como_usar' => [
            'Se você tiver mais de uma propriedade, escolha qual deseja ver no campo **Propriedade**.',
            'Passe o mouse sobre os gráficos para ver os valores exatos.',
            'Use **Ver tabela** e **Ver todas** para abrir os detalhes de cada bloco.',
        ],
        'dica' => 'Para analisar por safra ou período, use **Custos por talhão** e **Relatório de custos**, no grupo Análises do menu.',
    ],

    'propriedades' => [
        'index' => [
            'titulo' => 'Propriedades',
            'proposito' => 'Lista as propriedades rurais que você cadastrou. A propriedade é o ponto de partida do sistema: talhões, funcionários, máquinas, implementos e insumos pertencem sempre a uma propriedade.',
            'exibe' => [
                'Nome, proprietário, município/UF e área total de cada propriedade.',
                'Quantidade de talhões de cada uma.',
                'Somente as suas propriedades: cada usuário vê apenas os próprios dados.',
            ],
            'como_usar' => [
                'Clique em **Nova propriedade** para cadastrar uma propriedade.',
                'Digite na busca para encontrar uma propriedade pelo nome, proprietário ou município. O resultado aparece enquanto você digita.',
                'Clique no nome de uma propriedade para ver os talhões e os custos dela.',
                'Em telas maiores, escolha entre **Tabela** e **Cards** para mudar a forma de exibição.',
            ],
            'dica' => 'Uma propriedade que já tem talhões, recursos ou atividades não pode ser excluída, para que o histórico de custos não se perca.',
        ],

        'create' => [
            'titulo' => 'Nova propriedade',
            'proposito' => 'Cadastra uma propriedade rural. Depois de salvá-la, você poderá cadastrar os talhões e os recursos (funcionários, máquinas, implementos e insumos) dessa propriedade.',
            'exibe' => [
                '**Identificação**: nome da propriedade e nome do proprietário.',
                '**Localização e área**: município, UF e área total em hectares.',
                '**Informações adicionais**: observações livres sobre a propriedade.',
            ],
            'como_usar' => [
                'Preencha os campos. Os marcados como **(opcional)** podem ficar em branco.',
                'Clique em **Salvar**. Se algum campo estiver incorreto, a mensagem aparece logo abaixo dele.',
            ],
            'dica' => 'A área total limita os talhões: a soma das áreas dos talhões ativos não pode ultrapassar a área da propriedade.',
        ],

        'edit' => [
            'titulo' => 'Editar propriedade',
            'proposito' => 'Altera os dados cadastrais de uma propriedade que já existe.',
            'exibe' => [
                'Os mesmos campos do cadastro, já preenchidos com os dados atuais.',
            ],
            'como_usar' => [
                'Altere os campos desejados e clique em **Salvar**.',
                'Para desistir, clique em **Cancelar**: nada será alterado.',
            ],
            'dica' => 'A área total não pode ficar menor que a soma das áreas dos talhões ativos desta propriedade.',
        ],

        'show' => [
            'titulo' => 'Detalhes da propriedade',
            'proposito' => 'Mostra o resumo completo de uma propriedade: seus números, os talhões que a compõem e os recursos cadastrados nela.',
            'exibe' => [
                '**Indicadores**: área total, atividades registradas, custo total e custo médio por hectare.',
                '**Talhões e custos**: cada talhão com área, cultura, safra, custo total e custo por hectare.',
                '**Recursos da propriedade**: quantos funcionários, máquinas, implementos e insumos estão cadastrados.',
                '**Observações** registradas no cadastro.',
            ],
            'como_usar' => [
                'Clique em **Novo talhão** para dividir a propriedade em talhões.',
                'Clique em um talhão para ver as atividades que formam o custo dele.',
                'Clique em um recurso (funcionários, máquinas, implementos ou insumos) para abrir a lista dele já filtrada por esta propriedade.',
                'Use **Editar** para alterar os dados da propriedade.',
            ],
            'dica' => 'O custo médio por hectare é o custo total das atividades dividido pela área dos talhões.',
        ],
    ],

    'talhoes' => [
        'index' => [
            'titulo' => 'Talhões',
            'proposito' => 'Lista os talhões, que são as divisões da propriedade onde as atividades acontecem. É por talhão que o sistema apropria os custos e calcula o custo por hectare.',
            'exibe' => [
                'Nome, propriedade, área em hectares, cultura e safra de cada talhão.',
                'A situação do talhão: **Em cultivo**, **Em pousio** ou **Inativo**.',
            ],
            'como_usar' => [
                'Clique em **Novo talhão** para cadastrar um talhão.',
                'Digite na busca para encontrar um talhão pelo nome, cultura ou safra.',
                'Se tiver mais de uma propriedade, filtre a lista pelo campo **Propriedade**.',
                'Clique no nome de um talhão para ver os custos e as atividades dele.',
            ],
            'dica' => 'Um talhão com atividades registradas não pode ser excluído. Para tirá-lo de uso, mude a situação para **Inativo**.',
        ],

        'create' => [
            'titulo' => 'Novo talhão',
            'proposito' => 'Cadastra um talhão dentro de uma propriedade. Depois, as atividades realizadas nele formarão o seu custo.',
            'exibe' => [
                '**Identificação**: propriedade, nome do talhão e área em hectares.',
                '**Cultura e safra**: o que está plantado e a safra atual (ex.: 2025/2026).',
                '**Situação**: em cultivo, em pousio ou inativo.',
            ],
            'como_usar' => [
                'Escolha a propriedade e preencha os dados do talhão.',
                'Clique em **Salvar**.',
            ],
            'dica' => 'A soma das áreas dos talhões ativos não pode ultrapassar a área total da propriedade. Talhões inativos não ocupam área.',
        ],

        'edit' => [
            'titulo' => 'Editar talhão',
            'proposito' => 'Altera os dados de um talhão, como a cultura, a safra, a área ou a situação.',
            'exibe' => [
                'Os mesmos campos do cadastro, já preenchidos com os dados atuais.',
            ],
            'como_usar' => [
                'Ao iniciar uma nova safra, atualize a **Cultura** e a **Safra** do talhão.',
                'Altere os campos desejados e clique em **Salvar**.',
            ],
            'dica' => 'Cada atividade guarda a safra do talhão no momento em que foi registrada. Mudar a safra aqui não altera as atividades já lançadas.',
        ],

        'show' => [
            'titulo' => 'Detalhes do talhão',
            'proposito' => 'Mostra quanto o talhão custou e quais atividades formaram esse custo.',
            'exibe' => [
                'Propriedade, cultura, safra, área e situação do talhão.',
                '**Custos** separados em insumos, maquinário e mão de obra, com o custo total e o **custo por hectare**.',
                '**Atividades que formam o custo**: cada operação realizada no talhão, com data e valor.',
            ],
            'como_usar' => [
                'Clique em **Registrar atividade** para lançar uma operação neste talhão.',
                'Clique em uma atividade para ver os recursos utilizados nela.',
            ],
            'dica' => 'Custo por hectare = custo total do talhão ÷ área do talhão.',
        ],
    ],

    'funcionarios' => [
        'index' => [
            'titulo' => 'Funcionários',
            'proposito' => 'Lista as pessoas que trabalham nas atividades. O custo por hora de cada funcionário forma o custo de **mão de obra** das atividades.',
            'exibe' => [
                'Nome, função, propriedade e custo por hora de cada funcionário.',
                'A situação: **Ativo** ou **Inativo**.',
            ],
            'como_usar' => [
                'Clique em **Novo funcionário** para cadastrar.',
                'Digite na busca para encontrar um funcionário pelo nome ou pela função.',
                'Use **Editar** para alterar os dados ou a situação de um funcionário.',
            ],
            'dica' => 'Funcionários que já participaram de atividades não podem ser excluídos. Marque-os como **Inativo** para que não apareçam em novas atividades.',
        ],

        'create' => [
            'titulo' => 'Novo funcionário',
            'proposito' => 'Cadastra um funcionário para que ele possa ser informado na mão de obra das atividades.',
            'exibe' => [
                '**Dados do funcionário**: propriedade, nome e função.',
                '**Custo da mão de obra**: quanto custa uma hora de trabalho dele.',
                '**Situação**: ativo ou inativo.',
            ],
            'como_usar' => [
                'Preencha os dados e informe o custo por hora em reais.',
                'Clique em **Salvar**.',
            ],
            'dica' => 'Na atividade, o custo da mão de obra é calculado assim: horas trabalhadas × custo por hora.',
        ],

        'edit' => [
            'titulo' => 'Editar funcionário',
            'proposito' => 'Altera os dados de um funcionário, como a função, o custo por hora ou a situação.',
            'exibe' => [
                'Os mesmos campos do cadastro, já preenchidos com os dados atuais.',
            ],
            'como_usar' => [
                'Altere os campos desejados e clique em **Salvar**.',
                'Para deixar de usar o funcionário, mude a situação para **Inativo**.',
            ],
            'dica' => 'Um novo custo por hora vale apenas para as próximas atividades. As atividades já registradas mantêm o valor da época.',
        ],
    ],

    'maquinas' => [
        'index' => [
            'titulo' => 'Máquinas',
            'proposito' => 'Lista as máquinas usadas nas atividades, como tratores, pulverizadores e colheitadeiras. O custo por hora de cada máquina forma o custo de **maquinário** das atividades.',
            'exibe' => [
                'Nome, tipo, modelo/ano, propriedade e custo por hora de cada máquina.',
                'A situação: **Ativo**, **Em manutenção** ou **Inativo**.',
            ],
            'como_usar' => [
                'Clique em **Nova máquina** para cadastrar.',
                'Digite na busca para encontrar uma máquina pelo nome, tipo ou modelo.',
                'Use **Editar** para alterar os dados ou a situação de uma máquina.',
            ],
            'dica' => 'Somente máquinas ativas aparecem no cadastro de atividades. Uma máquina em manutenção volta a aparecer quando for marcada como ativa.',
        ],

        'create' => [
            'titulo' => 'Nova máquina',
            'proposito' => 'Cadastra uma máquina para que ela possa ser usada nas atividades.',
            'exibe' => [
                '**Identificação**: propriedade, nome, tipo, modelo e ano.',
                '**Custo de uso**: quanto custa uma hora de uso da máquina.',
                '**Situação**: ativo, em manutenção ou inativo.',
            ],
            'como_usar' => [
                'Preencha os dados e informe o custo por hora em reais.',
                'Clique em **Salvar**.',
            ],
            'dica' => 'O custo por hora da máquina já inclui os implementos que ela traciona, por isso implementos não têm custo próprio.',
        ],

        'edit' => [
            'titulo' => 'Editar máquina',
            'proposito' => 'Altera os dados de uma máquina, como o custo por hora ou a situação.',
            'exibe' => [
                'Os mesmos campos do cadastro, já preenchidos com os dados atuais.',
            ],
            'como_usar' => [
                'Altere os campos desejados e clique em **Salvar**.',
                'Se a máquina estiver parada, mude a situação para **Em manutenção**.',
            ],
            'dica' => 'Um novo custo por hora vale apenas para as próximas atividades. As atividades já registradas mantêm o valor da época.',
        ],
    ],

    'implementos' => [
        'index' => [
            'titulo' => 'Implementos',
            'proposito' => 'Lista os implementos, equipamentos acoplados às máquinas, como grades, plantadeiras e pulverizadores de arrasto. Eles ficam registrados nas atividades, mas não têm custo próprio.',
            'exibe' => [
                'Nome, tipo, modelo e propriedade de cada implemento.',
                'A situação: **Ativo**, **Em manutenção** ou **Inativo**.',
            ],
            'como_usar' => [
                'Clique em **Novo implemento** para cadastrar.',
                'Digite na busca para encontrar um implemento pelo nome, tipo ou modelo.',
                'Use **Editar** para alterar os dados ou a situação de um implemento.',
            ],
            'dica' => 'O custo de uso do implemento já está incluído no custo por hora da máquina que o traciona.',
        ],

        'create' => [
            'titulo' => 'Novo implemento',
            'proposito' => 'Cadastra um implemento para que ele possa ser associado às atividades.',
            'exibe' => [
                '**Identificação**: propriedade, nome, tipo e modelo.',
                '**Situação**: ativo, em manutenção ou inativo.',
            ],
            'como_usar' => [
                'Preencha os dados do implemento.',
                'Clique em **Salvar**.',
            ],
            'dica' => 'Implementos não têm campo de custo: o custo deles está no custo por hora da máquina.',
        ],

        'edit' => [
            'titulo' => 'Editar implemento',
            'proposito' => 'Altera os dados de um implemento, como o modelo ou a situação.',
            'exibe' => [
                'Os mesmos campos do cadastro, já preenchidos com os dados atuais.',
            ],
            'como_usar' => [
                'Altere os campos desejados e clique em **Salvar**.',
                'Para deixar de usar o implemento, mude a situação para **Inativo**.',
            ],
        ],
    ],

    'insumos' => [
        'index' => [
            'titulo' => 'Insumos',
            'proposito' => 'Lista os produtos aplicados nas lavouras, como sementes, fertilizantes e defensivos. O valor unitário de cada insumo forma o custo de **insumos** das atividades.',
            'exibe' => [
                'Nome, categoria, valor unitário e propriedade de cada insumo.',
                'A quantidade em estoque, apenas como informação.',
                'A situação: **Ativo** ou **Inativo**.',
            ],
            'como_usar' => [
                'Clique em **Novo insumo** para cadastrar.',
                'Digite na busca para encontrar um insumo pelo nome.',
                'Use **Editar** para atualizar o preço ou a situação de um insumo.',
            ],
            'dica' => 'O estoque é apenas informativo: registrar uma atividade não diminui a quantidade em estoque.',
        ],

        'create' => [
            'titulo' => 'Novo insumo',
            'proposito' => 'Cadastra um insumo para que ele possa ser usado nas atividades.',
            'exibe' => [
                '**Identificação**: propriedade, nome, categoria e unidade de medida (kg, L, t, saca...).',
                '**Valor e estoque**: preço de uma unidade e a quantidade disponível.',
                '**Situação**: ativo ou inativo.',
            ],
            'como_usar' => [
                'Escolha a unidade de medida e informe o valor de **uma** unidade.',
                'Clique em **Salvar**.',
            ],
            'dica' => 'Na atividade, o custo do insumo é calculado assim: quantidade utilizada × valor unitário.',
        ],

        'edit' => [
            'titulo' => 'Editar insumo',
            'proposito' => 'Altera os dados de um insumo, como o valor unitário, o estoque ou a situação.',
            'exibe' => [
                'Os mesmos campos do cadastro, já preenchidos com os dados atuais.',
            ],
            'como_usar' => [
                'Quando o preço mudar, atualize o **Valor unitário** e clique em **Salvar**.',
                'Para deixar de usar o insumo, mude a situação para **Inativo**.',
            ],
            'dica' => 'Um novo preço vale apenas para as próximas atividades. As atividades já registradas mantêm o valor da época.',
        ],
    ],

    'atividades' => [
        'index' => [
            'titulo' => 'Atividades agrícolas',
            'proposito' => 'Lista as operações realizadas nos talhões, como plantio, adubação, pulverização e colheita. Cada atividade guarda os recursos utilizados e o custo calculado.',
            'exibe' => [
                'Data, operação, talhão e safra de cada atividade.',
                'O custo com insumos, máquinas e mão de obra e o custo total.',
                'As atividades mais recentes aparecem primeiro.',
            ],
            'como_usar' => [
                'Clique em **Registrar atividade** para lançar uma operação.',
                'Digite na busca para encontrar atividades pela operação, talhão, descrição ou safra.',
                'Filtre a lista por **Propriedade**, **Talhão** ou **Operação**.',
                'Clique em uma atividade para ver o detalhamento do custo.',
            ],
            'dica' => 'O custo de cada atividade é calculado pelo sistema ao salvar, a partir dos recursos informados.',
        ],

        'create' => [
            'titulo' => 'Registrar atividade agrícola',
            'proposito' => 'Registra uma operação realizada em um talhão e os recursos usados nela. O sistema calcula o custo automaticamente.',
            'exibe' => [
                '**1. Dados da atividade**: propriedade, talhão, data, tipo de operação e descrição.',
                '**2. Insumos**: produtos aplicados e a quantidade de cada um.',
                '**3. Máquinas**: máquinas usadas e as horas trabalhadas.',
                '**4. Mão de obra**: funcionários e as horas trabalhadas.',
                '**5. Implementos**: equipamentos acoplados às máquinas (sem custo).',
                '**Resumo do custo**: prévia do custo de cada grupo e do total.',
            ],
            'como_usar' => [
                'Escolha o talhão, a data e o tipo de operação.',
                'Adicione os recursos usados com os botões de cada seção. Informe pelo menos um insumo, uma máquina ou um funcionário.',
                'Confira o resumo do custo e clique em **Salvar atividade**.',
            ],
            'dica' => 'A safra é copiada do talhão no momento do registro. Os preços também ficam guardados na atividade, então mudanças futuras nos cadastros não alteram este custo.',
        ],

        'edit' => [
            'titulo' => 'Editar atividade',
            'proposito' => 'Corrige uma atividade já registrada: data, operação, descrição ou recursos utilizados.',
            'exibe' => [
                'As mesmas seções do cadastro, já preenchidas com os dados da atividade.',
                'O **Resumo do custo** atualizado conforme as alterações.',
            ],
            'como_usar' => [
                'Altere as quantidades ou as horas, adicione ou remova recursos.',
                'Clique em **Salvar atividade** para recalcular o custo.',
            ],
            'dica' => 'Os recursos que já estavam na atividade mantêm o preço da época. Recursos novos entram com o preço atual do cadastro.',
        ],

        'show' => [
            'titulo' => 'Detalhes da atividade',
            'proposito' => 'Mostra como o custo da atividade foi formado, recurso por recurso.',
            'exibe' => [
                'Propriedade, talhão, safra, data, operação e descrição.',
                '**Custos**: insumos, máquinas, mão de obra e o custo total.',
                '**Recursos utilizados**: cada insumo (quantidade × custo unitário), máquina e funcionário (horas × custo por hora) e os implementos.',
            ],
            'como_usar' => [
                'Use **Editar** para corrigir a atividade.',
                'Use **Excluir** para remover a atividade. O custo dela deixa de ser somado ao talhão.',
            ],
            'dica' => 'Os valores exibidos são os da data do registro, mesmo que os preços tenham mudado depois.',
        ],
    ],

    'custos-talhoes' => [
        'index' => [
            'titulo' => 'Custos por talhão',
            'proposito' => 'Compara os custos acumulados de cada talhão. Ajuda a descobrir quais áreas custam mais e onde o dinheiro está sendo gasto.',
            'exibe' => [
                '**Indicadores**: custos com insumos, maquinário e mão de obra, custo total e custo médio por hectare.',
                '**Gráficos**: custo total por talhão, custo por hectare dos talhões, custo por hectare por cultura e composição dos custos.',
                '**Detalhamento por talhão**: tabela com os valores de cada talhão.',
            ],
            'como_usar' => [
                'Filtre por **Propriedade** e **Safra**. A página é atualizada assim que você escolhe uma opção.',
                'Passe o mouse sobre os gráficos para ver os valores exatos.',
                'Clique no nome de um talhão para ver as atividades que formam o custo.',
                'Clique em **Exportar PDF** para baixar o relatório.',
            ],
            'dica' => 'O custo por hectare permite comparar talhões de tamanhos diferentes: custo total do talhão ÷ área do talhão.',
        ],
    ],

    'relatorios' => [
        'index' => [
            'titulo' => 'Relatório de custos',
            'proposito' => 'Analisa os custos das atividades com filtros detalhados e gera um relatório em PDF, pronto para imprimir ou compartilhar.',
            'exibe' => [
                '**Indicadores**: custos com insumos, máquinas e mão de obra, custo total e custo por hectare.',
                '**Gráficos**: evolução mensal dos custos, custo por tipo de operação, composição dos custos e custo por hectare dos talhões.',
                '**Atividades que formaram o custo**: a lista das atividades encontradas.',
            ],
            'como_usar' => [
                'Escolha os filtros: propriedade, talhão, safra, data inicial e data final.',
                'Clique em **Aplicar filtros** para atualizar os números e os gráficos.',
                'Clique em **Gerar relatório em PDF** para baixar o relatório com os mesmos filtros.',
                'Use **Limpar filtros** para voltar a ver todas as atividades.',
            ],
            'dica' => 'Deixe os filtros em branco para analisar todas as atividades de todas as propriedades.',
        ],
    ],

    'assistente' => [
        'index' => [
            'titulo' => 'Assistente IA',
            'proposito' => 'Conversa com uma inteligência artificial que consulta os seus dados e faz cadastros a partir do que você pede, em linguagem natural. Ela usa as mesmas regras e cálculos das telas do sistema.',
            'exibe' => [
                '**Conversas**: o histórico das suas conversas com o assistente.',
                '**Mensagens**: as suas perguntas e as respostas da IA, com o provedor usado, as ferramentas consultadas e os tokens gastos.',
                '**Provedor**: a escolha entre **Claude** (Anthropic) e **OpenAI** para as próximas respostas.',
            ],
            'como_usar' => [
                'Escreva o que precisa, por exemplo: "quanto gastei no Talhão 01 nesta safra?" ou "registre uma adubação de ontem no Talhão 02".',
                'Para cadastrar, a IA mostra uma **prévia** com os dados e os custos calculados e pergunta se pode salvar. Responda "sim" para gravar.',
                'Use **Nova conversa** para mudar de assunto. O botão **Assistente IA**, no canto das outras telas, abre o mesmo chat sem sair da tela.',
            ],
            'dica' => 'O assistente só consulta e cadastra: para editar ou excluir, use as telas do sistema. Há um limite diário de mensagens por usuário.',
        ],
    ],

    'profile' => [
        'edit' => [
            'titulo' => 'Perfil',
            'proposito' => 'Mantém atualizados os dados da sua conta de acesso ao sistema.',
            'exibe' => [
                'Seu nome e seu e-mail.',
                'A opção de excluir a conta.',
            ],
            'como_usar' => [
                'Altere o nome ou o e-mail e clique em **Salvar**.',
                'Use as abas **Perfil**, **Segurança** e **Aparência** para navegar pelas configurações.',
            ],
            'dica' => 'Excluir a conta apaga também todas as propriedades, cadastros e atividades. Essa ação não pode ser desfeita.',
        ],
    ],

    'security' => [
        'edit' => [
            'titulo' => 'Segurança',
            'proposito' => 'Protege o acesso à sua conta.',
            'exibe' => [
                '**Atualizar senha**: troca da senha de acesso.',
                '**Proteções extras**, quando estiverem habilitadas no sistema: um código gerado no celular, pedido a cada entrada, e o acesso sem senha usando a biometria ou o bloqueio de tela do aparelho.',
            ],
            'como_usar' => [
                'Para trocar a senha, informe a senha atual, a nova senha e a confirmação, e clique em **Salvar**.',
                'As proteções extras são opcionais: ative-as somente se desejar.',
            ],
            'dica' => 'Use uma senha longa e que você não utilize em outros sites.',
        ],
    ],

    'appearance' => [
        'edit' => [
            'titulo' => 'Aparência',
            'proposito' => 'Escolhe como o sistema é exibido na tela.',
            'exibe' => [
                'As opções de tema: **Claro**, **Escuro** ou **Sistema**.',
            ],
            'como_usar' => [
                'Clique na opção desejada. A mudança é aplicada na hora.',
            ],
            'dica' => 'A opção **Sistema** acompanha automaticamente o tema configurado no seu computador ou celular.',
        ],
    ],

];
