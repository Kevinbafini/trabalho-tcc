<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use App\Services\GraficosCustoService;
use App\Services\IndicadoresCustoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * Relatório de custos em PDF (DomPDF), com os mesmos filtros da tela de relatórios.
 *
 * @phpstan-import-type FiltrosValidados from FiltroCustosRequest
 */
class RelatorioPdfController extends Controller
{
    /**
     * Quantidade máxima de atividades listadas no PDF.
     */
    private const LIMITE_DE_ATIVIDADES = 500;

    /**
     * Gera e abre o PDF no navegador.
     */
    public function __invoke(
        FiltroCustosRequest $request,
        #[CurrentUser] User $usuario,
        IndicadoresCustoService $indicadores,
        GraficosCustoService $graficos,
    ): Response {
        $filtros = $request->filtros();
        $resumo = $indicadores->resumo($usuario, $filtros);

        $talhoesComAtividades = $indicadores->custosPorTalhao($usuario, $filtros, somenteComAtividades: true);

        $pdf = Pdf::loadView('relatorios.pdf', [
            'usuario' => $usuario,
            'geradoEm' => now(),
            'logo' => 'data:image/png;base64,'.base64_encode(File::get(public_path('images/logo-scaa.png'))),
            'filtrosDescritos' => $this->descreverFiltros($usuario, $filtros),
            'resumo' => $resumo,
            'fatias' => $graficos->fatiasParaPdf($resumo),
            'colunasMensais' => $graficos->colunasParaPdf($graficos->custosPorMes($indicadores->custosPorMes($usuario, $filtros))),
            'barrasOperacoes' => $graficos->barrasParaPdf($graficos->custosPorOperacao($indicadores->custosPorOperacao($usuario, $filtros))),
            'custosPorTalhao' => $talhoesComAtividades,
            'barrasCustoPorHectare' => $graficos->barrasParaPdf($graficos->custoPorHectarePorTalhao($talhoesComAtividades)),
            'atividades' => $indicadores->atividades($usuario, $filtros)
                ->with('talhao')
                ->orderBy('data')
                ->orderBy('id')
                ->limit(self::LIMITE_DE_ATIVIDADES)
                ->get(),
            'limiteDeAtividades' => self::LIMITE_DE_ATIVIDADES,
            'cores' => GraficosCustoService::CORES,
        ])->setPaper('a4');

        $pdf->render();
        $this->numerarPaginas($pdf);

        return $pdf->stream('relatorio-de-custos-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Filtros em texto, para o cabeçalho do relatório.
     *
     * @param  FiltrosValidados  $filtros
     * @return array{Propriedade: string, Talhão: string, Safra: string, Período: string}
     */
    private function descreverFiltros(User $usuario, array $filtros): array
    {
        $propriedade = $filtros['propriedade_id']
            ? Propriedade::query()->doUsuario($usuario)->find($filtros['propriedade_id'])?->nome
            : null;
        $talhao = $filtros['talhao_id']
            ? Talhao::query()->doUsuario($usuario)->find($filtros['talhao_id'])?->nome
            : null;

        $inicio = $filtros['data_inicio'] ? now()->parse($filtros['data_inicio'])->format('d/m/Y') : null;
        $fim = $filtros['data_fim'] ? now()->parse($filtros['data_fim'])->format('d/m/Y') : null;

        $periodo = match (true) {
            $inicio !== null && $fim !== null => "{$inicio} a {$fim}",
            $inicio !== null => "A partir de {$inicio}",
            $fim !== null => "Até {$fim}",
            default => 'Todo o período',
        };

        return [
            'Propriedade' => $propriedade ?? 'Todas',
            'Talhão' => $talhao ?? 'Todos',
            'Safra' => $filtros['safra'] ?? 'Todas',
            'Período' => $periodo,
        ];
    }

    /**
     * Escreve "Página X de Y" no rodapé de todas as páginas.
     */
    private function numerarPaginas(DocumentoPdf $pdf): void
    {
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $fonte = $dompdf->getFontMetrics()->getFont('Helvetica');
        $tamanho = 7;

        // Alinha o número da página à margem direita, na mesma linha do texto do rodapé.
        $largura = $dompdf->getFontMetrics()->getTextWidth('Página 99 de 99', $fonte ?? 'Helvetica', $tamanho);
        $margemDireita = 14 * 72 / 25.4;

        $canvas->page_text(
            $canvas->get_width() - $margemDireita - $largura,
            $canvas->get_height() - 39,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fonte,
            $tamanho,
            [0.63, 0.63, 0.67],
        );
    }
}
