{{-- Aviso exibido enquanto nenhum provedor de IA tem chave configurada (ou o assistente está desligado). --}}
<flux:callout variant="warning" icon="key" class="mb-6">
    <flux:callout.heading>Assistente ainda não configurado</flux:callout.heading>
    <flux:callout.text>
        Para ativá-lo, cadastre ANTHROPIC_API_KEY (Claude) ou OPENAI_API_KEY (OpenAI) no arquivo .env do servidor.
    </flux:callout.text>
</flux:callout>
