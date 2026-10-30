<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\DadosDemonstracaoService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Configurações → Dados de demonstração: o usuário coloca na própria conta
 * (ou remove) as propriedades fictícias, para explorar o sistema já populado.
 * Os cadastros reais do usuário nunca são alterados.
 */
#[Title('Dados de demonstração')]
class DadosDemonstracao extends Component
{
    /**
     * @return array{propriedades: int, talhoes: int, atividades: int, custo_total: float}
     */
    #[Computed]
    public function resumo(): array
    {
        return app(DadosDemonstracaoService::class)->resumo($this->usuario());
    }

    public function ativar(DadosDemonstracaoService $demonstracao): void
    {
        $demonstracao->ativar($this->usuario());

        unset($this->resumo);

        Flux::toast(variant: 'success', text: 'Dados de demonstração ativados. Explore o Dashboard, os relatórios e o assistente.');
    }

    public function remover(DadosDemonstracaoService $demonstracao): void
    {
        $demonstracao->remover($this->usuario());

        unset($this->resumo);

        Flux::modal('remover-demonstracao')->close();
        Flux::toast(variant: 'success', text: 'Dados de demonstração removidos.');
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        return $usuario;
    }
}
