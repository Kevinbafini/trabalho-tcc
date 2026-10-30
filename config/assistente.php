<?php

/*
|--------------------------------------------------------------------------
| Assistente IA
|--------------------------------------------------------------------------
|
| Comportamento do chat com IA. As chaves e os modelos de cada provedor
| ficam em config/services.php (anthropic e openai).
|
*/

return [

    // Desliga o assistente inteiro (chat e servidor MCP continuam existindo, mas o chat não responde).
    'habilitado' => (bool) env('ASSISTENTE_HABILITADO', true),

    // Provedor sugerido ao abrir o chat: claude ou openai (o usuário pode trocar no seletor).
    'provedor_padrao' => env('ASSISTENTE_PROVEDOR_PADRAO', 'claude'),

    // Limites de uso por usuário, para controlar o custo com o sistema publicado.
    'mensagens_por_minuto' => (int) env('ASSISTENTE_MENSAGENS_POR_MINUTO', 6),
    'mensagens_por_dia' => (int) env('ASSISTENTE_MENSAGENS_POR_DIA', 60),

    // Quantas mensagens anteriores da conversa são enviadas à IA (as mais recentes).
    'historico' => (int) env('ASSISTENTE_HISTORICO', 12),

    // Máximo de rodadas de ferramentas por resposta (cada rodada é uma chamada à API).
    'rodadas_de_ferramentas' => (int) env('ASSISTENTE_RODADAS_DE_FERRAMENTAS', 6),

    // Tamanho máximo de uma mensagem do usuário (caracteres).
    'tamanho_da_mensagem' => 2000,

    // Sugestões exibidas no início de uma conversa (um clique envia o texto).
    'sugestoes' => [
        ['icone' => 'chart-pie', 'texto' => 'Faça um resumo dos meus custos nesta safra.'],
        ['icone' => 'map', 'texto' => 'Qual talhão tem o maior custo por hectare?'],
        ['icone' => 'clipboard-document-list', 'texto' => 'Quais foram as últimas atividades registradas?'],
        ['icone' => 'plus-circle', 'texto' => 'Quero registrar uma atividade agrícola.'],
    ],

];
