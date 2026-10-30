<?php

namespace App\Services\Ia;

use App\Contracts\AssistenteDeIa;
use App\Enums\ProvedorIa;

/**
 * Registro dos provedores de IA: entrega o assistente do provedor escolhido
 * no chat e diz quais estão configurados (com chave no .env).
 */
class Assistentes
{
    public function para(ProvedorIa $provedor): AssistenteDeIa
    {
        return match ($provedor) {
            ProvedorIa::Claude => app(AssistenteClaude::class),
            ProvedorIa::OpenAi => app(AssistenteOpenAi::class),
        };
    }

    /**
     * Todos os provedores, configurados ou não (o seletor mostra os dois).
     *
     * @return list<AssistenteDeIa>
     */
    public function todos(): array
    {
        return array_map(fn (ProvedorIa $provedor): AssistenteDeIa => $this->para($provedor), ProvedorIa::cases());
    }

    /**
     * Indica se o assistente pode responder: ligado e com pelo menos um provedor configurado.
     */
    public function disponivel(): bool
    {
        return config('assistente.habilitado') === true && $this->configurados() !== [];
    }

    /**
     * Provedor sugerido ao abrir o chat: o padrão do .env, se estiver
     * configurado; senão, o primeiro configurado.
     */
    public function padrao(): ProvedorIa
    {
        $padrao = ProvedorIa::tryFrom((string) config('assistente.provedor_padrao')) ?? ProvedorIa::Claude;
        $configurados = $this->configurados();

        return in_array($padrao, $configurados, true) || $configurados === [] ? $padrao : $configurados[0];
    }

    /**
     * @return list<ProvedorIa>
     */
    public function configurados(): array
    {
        return array_values(array_filter(
            ProvedorIa::cases(),
            fn (ProvedorIa $provedor): bool => $this->para($provedor)->configurado(),
        ));
    }
}
