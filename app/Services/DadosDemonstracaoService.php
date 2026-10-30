<?php

namespace App\Services;

use App\Enums\CategoriaInsumo;
use App\Enums\StatusEquipamento;
use App\Enums\StatusRecurso;
use App\Enums\StatusTalhao;
use App\Enums\TipoOperacao;
use App\Enums\UnidadeMedida;
use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Dados de demonstração de um usuário: duas propriedades fictícias completas
 * da safra 2025/2026, marcadas como "demonstracao" para que possam ser
 * removidas sem tocar nos cadastros reais.
 *
 *   - Fazenda Santa Helena: 50 ha, 5 talhões de soja, milho e amendoim e ≈100 atividades;
 *   - Sítio Boa Vista: 22 ha, 3 quadras de laranja e limão e 36 atividades.
 *
 * Cada usuário recebe seus próprios dados, permitindo que vários estudantes
 * façam o teste de usabilidade ao mesmo tempo sem interferir uns nos outros.
 * O usuário ativa ou remove os dados em Configurações → Dados de demonstração.
 *
 * Os valores são sempre os mesmos (sorteio com semente fixa), e alguns preços
 * sobem em 01/01/2026 para demonstrar a regra de histórico: as atividades
 * anteriores continuam com os valores antigos.
 *
 * @phpstan-type Etapa array{
 *     data: string,
 *     tipo: TipoOperacao,
 *     descricao: string,
 *     insumos?: array<string, float>,
 *     maquinas?: array<string, float>,
 *     funcionarios?: array<string, float>,
 *     implementos?: list<string>,
 * }
 */
class DadosDemonstracaoService
{
    public const NOME_DA_PROPRIEDADE = 'Fazenda Santa Helena (demonstração)';

    public const NOME_DO_SITIO = 'Sítio Boa Vista (demonstração)';

    private const DATA_DO_REAJUSTE = '2026-01-01';

    private Randomizer $sorteio;

    /** @var array<string, Insumo> */
    private array $insumos = [];

    /** @var array<string, Maquina> */
    private array $maquinas = [];

    /** @var array<string, Funcionario> */
    private array $funcionarios = [];

    /** @var array<string, Implemento> */
    private array $implementos = [];

    public function __construct(private CustoAtividadeService $custos) {}

    /**
     * Indica se o usuário está com os dados de demonstração na conta.
     */
    public function ativo(User $usuario): bool
    {
        return $usuario->propriedades()->where('demonstracao', true)->exists();
    }

    /**
     * Coloca os dados de demonstração na conta do usuário (se ainda não estiverem).
     *
     * @return bool true quando os dados foram criados agora
     */
    public function ativar(User $usuario): bool
    {
        if ($this->ativo($usuario)) {
            return false;
        }

        $this->gerar($usuario);

        return true;
    }

    /**
     * Remove as propriedades de demonstração do usuário com tudo o que há nelas
     * (talhões, recursos e atividades, inclusive o que ele alterou ou cadastrou
     * nelas). As demais propriedades do usuário não são tocadas.
     *
     * @return int quantidade de propriedades removidas
     */
    public function remover(User $usuario): int
    {
        return DB::transaction(function () use ($usuario): int {
            $propriedades = $usuario->propriedades()->where('demonstracao', true)->get();

            foreach ($propriedades as $propriedade) {
                // As atividades saem primeiro: elas protegem talhões e recursos contra exclusão.
                $propriedade->atividades()->delete();
                $propriedade->delete();
            }

            return $propriedades->count();
        });
    }

    /**
     * Números dos dados de demonstração do usuário (para a tela de configurações).
     *
     * @return array{propriedades: int, talhoes: int, atividades: int, custo_total: float}
     */
    public function resumo(User $usuario): array
    {
        $propriedades = $usuario->propriedades()->where('demonstracao', true)->select('id');

        return [
            'propriedades' => $usuario->propriedades()->where('demonstracao', true)->count(),
            'talhoes' => Talhao::query()->whereIn('propriedade_id', $propriedades)->count(),
            'atividades' => AtividadeAgricola::query()->whereIn('propriedade_id', $propriedades)->count(),
            'custo_total' => round((float) AtividadeAgricola::query()->whereIn('propriedade_id', $propriedades)->sum('custo_total'), 2),
        ];
    }

    /**
     * Cria as propriedades de demonstração com todos os dados para o usuário.
     *
     * @return Propriedade a fazenda (a maior das duas propriedades)
     */
    public function gerar(User $usuario): Propriedade
    {
        $this->sorteio = new Randomizer(new Mt19937(2026));

        return DB::transaction(function () use ($usuario): Propriedade {
            $fazenda = $this->criarPropriedade($usuario, [
                'nome' => self::NOME_DA_PROPRIEDADE,
                'proprietario' => 'João Carlos Pereira',
                'municipio' => 'Bebedouro',
                'uf' => 'SP',
                'area_total' => 50,
                'observacoes' => 'Propriedade fictícia criada para demonstração do sistema e para os testes de usabilidade. A área restante (3 ha) corresponde à sede, carreadores e área de preservação.',
            ]);

            $talhoes = $this->criarTalhoes($fazenda);
            $this->criarRecursos($fazenda);
            $this->criarAtividades(
                $fazenda,
                $talhoes,
                fn (Talhao $talhao): array => [...$this->etapasDaCultura($talhao->cultura), ...$this->etapasComuns()],
                fn () => $this->aplicarReajusteDePrecos(),
            );

            $this->criarSitio($usuario);

            return $fazenda;
        });
    }

    /**
     * Cria uma propriedade marcada como demonstração (a marcação não vem dos formulários).
     *
     * @param  array<string, mixed>  $dados
     */
    private function criarPropriedade(User $usuario, array $dados): Propriedade
    {
        $propriedade = new Propriedade($dados);
        $propriedade->forceFill(['demonstracao' => true]);
        $usuario->propriedades()->save($propriedade);

        return $propriedade;
    }

    /**
     * Segunda propriedade: um pomar de citros (Bebedouro é a capital nacional da laranja).
     */
    private function criarSitio(User $usuario): void
    {
        // Os recursos do sítio são outros, mesmo os de nome igual aos da fazenda.
        $this->insumos = $this->maquinas = $this->funcionarios = $this->implementos = [];

        $sitio = $this->criarPropriedade($usuario, [
            'nome' => self::NOME_DO_SITIO,
            'proprietario' => 'Maria Aparecida Santos',
            'municipio' => 'Bebedouro',
            'uf' => 'SP',
            'area_total' => 22,
            'observacoes' => 'Pomar fictício de citros criado para demonstração. A área restante (1,5 ha) corresponde à sede e aos carreadores.',
        ]);

        $talhoes = [];

        foreach ([['Quadra 01', 8.0, 'Laranja'], ['Quadra 02', 7.5, 'Laranja'], ['Quadra 03', 5.0, 'Limão']] as [$nome, $area, $cultura]) {
            $talhoes[$nome] = $sitio->talhoes()->create([
                'nome' => $nome, 'area' => $area, 'cultura' => $cultura, 'safra' => '2025/2026', 'status' => StatusTalhao::EmCultivo,
            ]);
        }

        foreach ([
            ['Pedro Henrique Costa', 'Tratorista', 27.00],
            ['Sebastião Ribeiro', 'Trabalhador rural', 19.50],
            ['Ana Paula Martins', 'Técnica agrícola', 42.00],
        ] as [$nome, $funcao, $custoHora]) {
            $this->funcionarios[$nome] = $sitio->funcionarios()->create([
                'nome' => $nome, 'funcao' => $funcao, 'custo_hora' => $custoHora, 'status' => StatusRecurso::Ativo,
            ]);
        }

        foreach ([
            ['Trator Valtra A950', 'Trator', 'A950', 2017, 140.00],
            ['Trator Massey Ferguson 275', 'Trator', '275', 2012, 110.00],
        ] as [$nome, $tipo, $modelo, $ano, $custoHora]) {
            $this->maquinas[$nome] = $sitio->maquinas()->create([
                'nome' => $nome, 'tipo' => $tipo, 'modelo' => $modelo, 'ano' => $ano, 'custo_hora' => $custoHora, 'status' => StatusEquipamento::Ativo,
            ]);
        }

        foreach ([
            ['Atomizador Jacto Arbus 2000', 'Pulverização'],
            ['Roçadeira ecológica', 'Roçada'],
            ['Adubadeira centrífuga', 'Adubação'],
            ['Carreta agrícola', 'Transporte'],
        ] as [$nome, $tipo]) {
            $this->implementos[$nome] = $sitio->implementos()->create([
                'nome' => $nome, 'tipo' => $tipo, 'status' => StatusEquipamento::Ativo,
            ]);
        }

        foreach ([
            ['Fertilizante NPK 20-05-20', CategoriaInsumo::Fertilizante, UnidadeMedida::Quilograma, 4.10, 1500],
            ['Calcário dolomítico', CategoriaInsumo::Corretivo, UnidadeMedida::Tonelada, 185.00, 6],
            ['Abamectina', CategoriaInsumo::Inseticida, UnidadeMedida::Litro, 68.00, 10],
            ['Imidacloprido', CategoriaInsumo::Inseticida, UnidadeMedida::Litro, 120.00, 8],
            ['Oxicloreto de cobre', CategoriaInsumo::Fungicida, UnidadeMedida::Quilograma, 42.00, 40],
            ['Glifosato', CategoriaInsumo::Herbicida, UnidadeMedida::Litro, 28.00, 60],
            ['Óleo mineral (adjuvante)', CategoriaInsumo::Outro, UnidadeMedida::Litro, 18.00, 40],
        ] as [$nome, $categoria, $unidade, $valor, $estoque]) {
            $this->insumos[$nome] = $sitio->insumos()->create([
                'nome' => $nome, 'categoria' => $categoria, 'unidade' => $unidade, 'valor_unitario' => $valor, 'quantidade_estoque' => $estoque, 'status' => StatusRecurso::Ativo,
            ]);
        }

        $this->criarAtividades(
            $sitio,
            $talhoes,
            fn (Talhao $talhao): array => $this->etapasDoPomar($talhao->cultura),
            function (): void {
                $this->insumos['Fertilizante NPK 20-05-20']->update(['valor_unitario' => 4.45]);
                $this->insumos['Imidacloprido']->update(['valor_unitario' => 128.00]);
            },
        );
    }

    /**
     * @return array<string, Talhao> [cultura do talhão => talhões]
     */
    private function criarTalhoes(Propriedade $propriedade): array
    {
        $talhoes = [];

        foreach ([
            ['Talhão 01', 12.0, 'Soja'],
            ['Talhão 02', 10.5, 'Soja'],
            ['Talhão 03', 9.5, 'Milho'],
            ['Talhão 04', 8.0, 'Milho'],
            ['Talhão 05', 7.0, 'Amendoim'],
        ] as [$nome, $area, $cultura]) {
            $talhoes[$nome] = $propriedade->talhoes()->create([
                'nome' => $nome,
                'area' => $area,
                'cultura' => $cultura,
                'safra' => '2025/2026',
                'status' => StatusTalhao::EmCultivo,
            ]);
        }

        return $talhoes;
    }

    private function criarRecursos(Propriedade $propriedade): void
    {
        foreach ([
            ['Carlos Henrique Souza', 'Operador de máquinas', 32.00],
            ['José Aparecido Lima', 'Tratorista', 28.50],
            ['Marcos Vinícius Rocha', 'Tratorista', 28.50],
            ['Antônio Ferreira', 'Trabalhador rural', 19.80],
            ['Luiz Fernando Alves', 'Encarregado de campo', 38.00],
        ] as [$nome, $funcao, $custoHora]) {
            $this->funcionarios[$nome] = $propriedade->funcionarios()->create([
                'nome' => $nome, 'funcao' => $funcao, 'custo_hora' => $custoHora, 'status' => StatusRecurso::Ativo,
            ]);
        }

        foreach ([
            ['Trator John Deere 6110J', 'Trator', '6110J', 2019, 185.00],
            ['Trator Massey Ferguson 4707', 'Trator', '4707', 2016, 150.00],
            ['Pulverizador Jacto Uniport 3030', 'Pulverizador autopropelido', 'Uniport 3030', 2020, 260.00],
            ['Colhedora John Deere S540', 'Colhedora', 'S540', 2018, 620.00],
        ] as [$nome, $tipo, $modelo, $ano, $custoHora]) {
            $this->maquinas[$nome] = $propriedade->maquinas()->create([
                'nome' => $nome, 'tipo' => $tipo, 'modelo' => $modelo, 'ano' => $ano, 'custo_hora' => $custoHora, 'status' => StatusEquipamento::Ativo,
            ]);
        }

        foreach ([
            ['Grade aradora intermediária', 'Preparo do solo'],
            ['Distribuidor de calcário e adubo', 'Adubação'],
            ['Plantadeira 9 linhas', 'Plantio'],
            ['Plataforma de grãos', 'Colheita'],
            ['Plataforma de milho', 'Colheita'],
            ['Arrancador-invertedor de amendoim', 'Colheita'],
        ] as [$nome, $tipo]) {
            $this->implementos[$nome] = $propriedade->implementos()->create([
                'nome' => $nome, 'tipo' => $tipo, 'status' => StatusEquipamento::Ativo,
            ]);
        }

        foreach ([
            ['Semente de soja', CategoriaInsumo::Semente, UnidadeMedida::Quilograma, 9.80, 400],
            ['Semente de milho híbrido', CategoriaInsumo::Semente, UnidadeMedida::Saca, 780.00, 4],
            ['Semente de amendoim', CategoriaInsumo::Semente, UnidadeMedida::Quilograma, 14.50, 0],
            ['Calcário dolomítico', CategoriaInsumo::Corretivo, UnidadeMedida::Tonelada, 185.00, 12],
            ['Gesso agrícola', CategoriaInsumo::Corretivo, UnidadeMedida::Tonelada, 160.00, 5],
            ['Fertilizante NPK 04-14-08', CategoriaInsumo::Fertilizante, UnidadeMedida::Quilograma, 3.90, 2500],
            ['Ureia', CategoriaInsumo::Fertilizante, UnidadeMedida::Quilograma, 3.20, 1200],
            ['Cloreto de potássio', CategoriaInsumo::Fertilizante, UnidadeMedida::Quilograma, 3.40, 800],
            ['Superfosfato simples', CategoriaInsumo::Fertilizante, UnidadeMedida::Quilograma, 2.10, 500],
            ['Glifosato', CategoriaInsumo::Herbicida, UnidadeMedida::Litro, 28.00, 120],
            ['2,4-D', CategoriaInsumo::Herbicida, UnidadeMedida::Litro, 32.00, 20],
            ['Atrazina', CategoriaInsumo::Herbicida, UnidadeMedida::Litro, 30.00, 40],
            ['Clorantraniliprole', CategoriaInsumo::Inseticida, UnidadeMedida::Litro, 520.00, 2],
            ['Lambda-cialotrina', CategoriaInsumo::Inseticida, UnidadeMedida::Litro, 95.00, 6],
            ['Azoxistrobina + ciproconazol', CategoriaInsumo::Fungicida, UnidadeMedida::Litro, 165.00, 15],
            ['Tebuconazol', CategoriaInsumo::Fungicida, UnidadeMedida::Litro, 75.00, 10],
            ['Inoculante para soja', CategoriaInsumo::Outro, UnidadeMedida::Unidade, 12.00, 30],
            ['Óleo mineral (adjuvante)', CategoriaInsumo::Outro, UnidadeMedida::Litro, 18.00, 60],
        ] as [$nome, $categoria, $unidade, $valor, $estoque]) {
            $this->insumos[$nome] = $propriedade->insumos()->create([
                'nome' => $nome,
                'categoria' => $categoria,
                'unidade' => $unidade,
                'valor_unitario' => $valor,
                'quantidade_estoque' => $estoque,
                'status' => StatusRecurso::Ativo,
            ]);
        }
    }

    /**
     * Lança as atividades em ordem cronológica, usando o CustoAtividadeService
     * (o mesmo cálculo das atividades cadastradas pela tela).
     *
     * @param  array<string, Talhao>  $talhoes
     * @param  callable(Talhao): list<Etapa>  $etapasDoTalhao
     * @param  callable(): void  $reajuste  aplicado antes da primeira atividade de 2026
     */
    private function criarAtividades(Propriedade $propriedade, array $talhoes, callable $etapasDoTalhao, callable $reajuste): void
    {
        $planejadas = [];

        foreach ($talhoes as $talhao) {
            foreach ($etapasDoTalhao($talhao) as $etapa) {
                $planejadas[] = ['talhao' => $talhao, 'etapa' => $etapa];
            }
        }

        usort($planejadas, fn (array $a, array $b): int => [$a['etapa']['data'], $a['talhao']->nome] <=> [$b['etapa']['data'], $b['talhao']->nome]);

        $reajusteAplicado = false;

        foreach ($planejadas as ['talhao' => $talhao, 'etapa' => $etapa]) {
            if (! $reajusteAplicado && $etapa['data'] >= self::DATA_DO_REAJUSTE) {
                $reajuste();
                $reajusteAplicado = true;
            }

            $this->custos->salvar(new AtividadeAgricola, $propriedade, [
                'talhao_id' => $talhao->id,
                'data' => $etapa['data'],
                'tipo' => $etapa['tipo'],
                'descricao' => $etapa['descricao'],
                'insumos' => $this->linhas($etapa['insumos'] ?? [], $this->insumos, $talhao->area, 2),
                'maquinas' => $this->linhas($etapa['maquinas'] ?? [], $this->maquinas, $talhao->area, 1),
                'funcionarios' => $this->linhas($etapa['funcionarios'] ?? [], $this->funcionarios, $talhao->area, 1),
                'implementos' => array_map(
                    fn (string $nome): int => $this->implementos[$nome]->id,
                    $etapa['implementos'] ?? [],
                ),
            ]);
        }
    }

    /**
     * Converte doses por hectare em quantidades do talhão, com pequena variação
     * (±8%) para que os talhões não fiquem idênticos.
     *
     * @template TRecurso of Insumo|Maquina|Funcionario
     *
     * @param  array<string, float>  $dosesPorHectare  [nome do recurso => dose ou horas por hectare]
     * @param  array<string, TRecurso>  $recursos
     * @return list<array{id: int, quantidade: float}>
     */
    private function linhas(array $dosesPorHectare, array $recursos, float $area, int $casasDecimais): array
    {
        $linhas = [];

        foreach ($dosesPorHectare as $nome => $dose) {
            $quantidade = round($dose * $area * $this->sorteio->getFloat(0.92, 1.08), $casasDecimais);

            $linhas[] = [
                'id' => $recursos[$nome]->id,
                'quantidade' => max($quantidade, 0.5),
            ];
        }

        return $linhas;
    }

    /**
     * Reajuste de preços em janeiro: as atividades já lançadas mantêm os valores antigos.
     */
    private function aplicarReajusteDePrecos(): void
    {
        $this->insumos['Ureia']->update(['valor_unitario' => 3.65]);
        $this->insumos['Glifosato']->update(['valor_unitario' => 31.50]);
        $this->insumos['Azoxistrobina + ciproconazol']->update(['valor_unitario' => 178.00]);
        $this->maquinas['Trator John Deere 6110J']->update(['custo_hora' => 195.00]);
        $this->funcionarios['José Aparecido Lima']->update(['custo_hora' => 30.00]);
        $this->funcionarios['Marcos Vinícius Rocha']->update(['custo_hora' => 30.00]);
    }

    /**
     * Etapas específicas de cada cultura na safra 2025/2026 (doses e horas por hectare).
     *
     * @return list<Etapa>
     */
    private function etapasDaCultura(string $cultura): array
    {
        $calagem = ['tipo' => TipoOperacao::PreparoSolo, 'maquinas' => ['Trator Massey Ferguson 4707' => 0.5], 'funcionarios' => ['José Aparecido Lima' => 0.5], 'implementos' => ['Distribuidor de calcário e adubo']];
        $gradagem = ['tipo' => TipoOperacao::PreparoSolo, 'descricao' => 'Gradagem para incorporação do calcário', 'maquinas' => ['Trator John Deere 6110J' => 0.8], 'funcionarios' => ['Marcos Vinícius Rocha' => 0.8], 'implementos' => ['Grade aradora intermediária']];
        $pulverizacao = ['tipo' => TipoOperacao::Pulverizacao, 'maquinas' => ['Pulverizador Jacto Uniport 3030' => 0.25], 'funcionarios' => ['Carlos Henrique Souza' => 0.25]];
        $dessecacao = [...$pulverizacao, 'descricao' => 'Dessecação pré-plantio', 'insumos' => ['Glifosato' => 3.0, 'Óleo mineral (adjuvante)' => 0.5]];
        $cobertura = ['tipo' => TipoOperacao::Adubacao, 'maquinas' => ['Trator Massey Ferguson 4707' => 0.4], 'funcionarios' => ['Marcos Vinícius Rocha' => 0.4], 'implementos' => ['Distribuidor de calcário e adubo']];

        return match ($cultura) {
            'Soja' => [
                [...$calagem, 'data' => '2025-09-02', 'descricao' => 'Calagem', 'insumos' => ['Calcário dolomítico' => 2.0]],
                [...$gradagem, 'data' => '2025-09-10'],
                [...$dessecacao, 'data' => '2025-10-01', 'insumos' => ['Glifosato' => 3.0, '2,4-D' => 1.0, 'Óleo mineral (adjuvante)' => 0.5]],
                ['data' => '2025-10-15', 'tipo' => TipoOperacao::Plantio, 'descricao' => 'Plantio da soja com adubação de base e inoculação', 'insumos' => ['Semente de soja' => 60, 'Fertilizante NPK 04-14-08' => 300, 'Inoculante para soja' => 1], 'maquinas' => ['Trator John Deere 6110J' => 0.9], 'funcionarios' => ['José Aparecido Lima' => 0.9, 'Antônio Ferreira' => 0.5], 'implementos' => ['Plantadeira 9 linhas']],
                [...$pulverizacao, 'data' => '2025-11-12', 'descricao' => 'Herbicida pós-emergente', 'insumos' => ['Glifosato' => 2.0, 'Óleo mineral (adjuvante)' => 0.5]],
                [...$cobertura, 'data' => '2025-11-28', 'descricao' => 'Adubação de cobertura com potássio', 'insumos' => ['Cloreto de potássio' => 100]],
                [...$pulverizacao, 'data' => '2025-12-10', 'descricao' => 'Controle de lagartas', 'insumos' => ['Clorantraniliprole' => 0.05, 'Óleo mineral (adjuvante)' => 0.3]],
                [...$pulverizacao, 'data' => '2025-12-22', 'descricao' => 'Fungicida preventivo contra ferrugem', 'insumos' => ['Azoxistrobina + ciproconazol' => 0.3, 'Óleo mineral (adjuvante)' => 0.5]],
                [...$pulverizacao, 'data' => '2026-01-08', 'descricao' => 'Controle de percevejos', 'insumos' => ['Lambda-cialotrina' => 0.15]],
                [...$pulverizacao, 'data' => '2026-01-16', 'descricao' => 'Segunda aplicação de fungicida', 'insumos' => ['Azoxistrobina + ciproconazol' => 0.3, 'Óleo mineral (adjuvante)' => 0.5]],
                ['data' => '2026-02-18', 'tipo' => TipoOperacao::Colheita, 'descricao' => 'Colheita da soja', 'maquinas' => ['Colhedora John Deere S540' => 0.6, 'Trator Massey Ferguson 4707' => 0.3], 'funcionarios' => ['Carlos Henrique Souza' => 0.6, 'José Aparecido Lima' => 0.3, 'Luiz Fernando Alves' => 0.2], 'implementos' => ['Plataforma de grãos']],
            ],
            'Milho' => [
                [...$calagem, 'data' => '2025-09-05', 'descricao' => 'Calagem e gessagem', 'insumos' => ['Calcário dolomítico' => 2.0, 'Gesso agrícola' => 1.0]],
                [...$gradagem, 'data' => '2025-09-15'],
                [...$dessecacao, 'data' => '2025-10-06'],
                ['data' => '2025-10-20', 'tipo' => TipoOperacao::Plantio, 'descricao' => 'Plantio do milho com adubação de base', 'insumos' => ['Semente de milho híbrido' => 1.2, 'Fertilizante NPK 04-14-08' => 350], 'maquinas' => ['Trator John Deere 6110J' => 0.9], 'funcionarios' => ['José Aparecido Lima' => 0.9, 'Antônio Ferreira' => 0.5], 'implementos' => ['Plantadeira 9 linhas']],
                [...$pulverizacao, 'data' => '2025-11-05', 'descricao' => 'Herbicida pós-emergente', 'insumos' => ['Atrazina' => 3.0, 'Óleo mineral (adjuvante)' => 0.5]],
                [...$cobertura, 'data' => '2025-11-20', 'descricao' => 'Primeira cobertura com ureia', 'insumos' => ['Ureia' => 150]],
                [...$pulverizacao, 'data' => '2025-12-12', 'descricao' => 'Controle de lagarta-do-cartucho', 'insumos' => ['Clorantraniliprole' => 0.05, 'Óleo mineral (adjuvante)' => 0.3]],
                [...$cobertura, 'data' => '2026-01-06', 'descricao' => 'Segunda cobertura com ureia', 'insumos' => ['Ureia' => 100]],
                [...$pulverizacao, 'data' => '2026-01-20', 'descricao' => 'Fungicida', 'insumos' => ['Azoxistrobina + ciproconazol' => 0.3, 'Óleo mineral (adjuvante)' => 0.5]],
                [...$pulverizacao, 'data' => '2026-02-10', 'descricao' => 'Controle de percevejos', 'insumos' => ['Lambda-cialotrina' => 0.15]],
                ['data' => '2026-03-20', 'tipo' => TipoOperacao::Colheita, 'descricao' => 'Colheita do milho', 'maquinas' => ['Colhedora John Deere S540' => 0.7, 'Trator Massey Ferguson 4707' => 0.3], 'funcionarios' => ['Carlos Henrique Souza' => 0.7, 'José Aparecido Lima' => 0.3, 'Luiz Fernando Alves' => 0.2], 'implementos' => ['Plataforma de milho']],
            ],
            default => [
                [...$calagem, 'data' => '2025-09-08', 'descricao' => 'Calagem e gessagem', 'insumos' => ['Calcário dolomítico' => 2.0, 'Gesso agrícola' => 1.5]],
                [...$gradagem, 'data' => '2025-09-20'],
                [...$dessecacao, 'data' => '2025-10-08'],
                ['data' => '2025-10-25', 'tipo' => TipoOperacao::Plantio, 'descricao' => 'Plantio do amendoim com adubação de base', 'insumos' => ['Semente de amendoim' => 120, 'Superfosfato simples' => 250], 'maquinas' => ['Trator John Deere 6110J' => 0.9], 'funcionarios' => ['José Aparecido Lima' => 0.9, 'Antônio Ferreira' => 0.5], 'implementos' => ['Plantadeira 9 linhas']],
                ['data' => '2025-11-15', 'tipo' => TipoOperacao::TratosCulturais, 'descricao' => 'Capina manual nas linhas', 'funcionarios' => ['Antônio Ferreira' => 4.0, 'Marcos Vinícius Rocha' => 2.0]],
                [...$pulverizacao, 'data' => '2025-11-25', 'descricao' => 'Fungicida (mancha-castanha)', 'insumos' => ['Tebuconazol' => 0.5]],
                [...$pulverizacao, 'data' => '2025-12-10', 'descricao' => 'Fungicida (mancha-preta)', 'insumos' => ['Azoxistrobina + ciproconazol' => 0.3, 'Óleo mineral (adjuvante)' => 0.5]],
                [...$pulverizacao, 'data' => '2025-12-28', 'descricao' => 'Controle de tripes', 'insumos' => ['Lambda-cialotrina' => 0.15]],
                [...$pulverizacao, 'data' => '2026-01-12', 'descricao' => 'Fungicida', 'insumos' => ['Tebuconazol' => 0.5]],
                [...$pulverizacao, 'data' => '2026-01-26', 'descricao' => 'Fungicida', 'insumos' => ['Azoxistrobina + ciproconazol' => 0.3, 'Óleo mineral (adjuvante)' => 0.5]],
                ['data' => '2026-03-05', 'tipo' => TipoOperacao::Colheita, 'descricao' => 'Arranquio e enleiramento', 'maquinas' => ['Trator John Deere 6110J' => 0.8], 'funcionarios' => ['José Aparecido Lima' => 0.8], 'implementos' => ['Arrancador-invertedor de amendoim']],
                ['data' => '2026-03-15', 'tipo' => TipoOperacao::Colheita, 'descricao' => 'Recolhimento do amendoim', 'maquinas' => ['Colhedora John Deere S540' => 0.7], 'funcionarios' => ['Carlos Henrique Souza' => 0.7, 'Luiz Fernando Alves' => 0.3]],
            ],
        };
    }

    /**
     * Etapas do pomar de citros na safra 2025/2026 (doses e horas por hectare).
     * O limão é colhido antes da laranja.
     *
     * @return list<Etapa>
     */
    private function etapasDoPomar(string $cultura): array
    {
        $atomizacao = ['tipo' => TipoOperacao::Pulverizacao, 'maquinas' => ['Trator Valtra A950' => 0.6], 'funcionarios' => ['Pedro Henrique Costa' => 0.6], 'implementos' => ['Atomizador Jacto Arbus 2000']];
        $rocada = ['tipo' => TipoOperacao::TratosCulturais, 'descricao' => 'Roçada nas entrelinhas', 'maquinas' => ['Trator Massey Ferguson 275' => 0.7], 'funcionarios' => ['Pedro Henrique Costa' => 0.7], 'implementos' => ['Roçadeira ecológica']];
        $adubacao = ['tipo' => TipoOperacao::Adubacao, 'maquinas' => ['Trator Massey Ferguson 275' => 0.5], 'funcionarios' => ['Pedro Henrique Costa' => 0.5], 'implementos' => ['Adubadeira centrífuga']];

        return [
            [...$adubacao, 'data' => '2025-08-20', 'descricao' => 'Calagem', 'insumos' => ['Calcário dolomítico' => 1.5]],
            [...$adubacao, 'data' => '2025-09-16', 'descricao' => 'Adubação de primavera', 'insumos' => ['Fertilizante NPK 20-05-20' => 400]],
            [...$atomizacao, 'data' => '2025-10-08', 'descricao' => 'Controle do psilídeo (greening)', 'insumos' => ['Imidacloprido' => 0.3, 'Óleo mineral (adjuvante)' => 1.0]],
            [...$rocada, 'data' => '2025-11-04'],
            [...$atomizacao, 'data' => '2025-11-26', 'descricao' => 'Controle do ácaro da leprose', 'insumos' => ['Abamectina' => 0.4, 'Óleo mineral (adjuvante)' => 1.0]],
            [...$adubacao, 'data' => '2025-12-16', 'descricao' => 'Adubação de verão', 'insumos' => ['Fertilizante NPK 20-05-20' => 300]],
            [...$atomizacao, 'data' => '2026-01-21', 'descricao' => 'Fungicida cúprico contra o cancro cítrico', 'insumos' => ['Oxicloreto de cobre' => 2.0]],
            [...$rocada, 'data' => '2026-02-19'],
            [...$atomizacao, 'data' => '2026-03-11', 'descricao' => 'Controle do psilídeo (greening)', 'insumos' => ['Imidacloprido' => 0.3, 'Óleo mineral (adjuvante)' => 1.0]],
            ['data' => '2026-04-08', 'tipo' => TipoOperacao::TratosCulturais, 'descricao' => 'Inspeção de greening e erradicação de plantas doentes', 'funcionarios' => ['Ana Paula Martins' => 0.3, 'Sebastião Ribeiro' => 0.6]],
            ['data' => $cultura === 'Limão' ? '2026-03-25' : '2026-05-20', 'tipo' => TipoOperacao::Colheita, 'descricao' => 'Colheita manual e transporte até o carreador', 'maquinas' => ['Trator Massey Ferguson 275' => 1.0], 'funcionarios' => ['Sebastião Ribeiro' => 6.0, 'Pedro Henrique Costa' => 1.0], 'implementos' => ['Carreta agrícola']],
            ['data' => '2026-06-24', 'tipo' => TipoOperacao::Pulverizacao, 'descricao' => 'Herbicida na linha de plantio', 'insumos' => ['Glifosato' => 2.0], 'maquinas' => ['Trator Valtra A950' => 0.4], 'funcionarios' => ['Pedro Henrique Costa' => 0.4], 'implementos' => ['Atomizador Jacto Arbus 2000']],
        ];
    }

    /**
     * Etapas realizadas em todos os talhões: monitoramento mensal e manejo de entressafra.
     *
     * @return list<Etapa>
     */
    private function etapasComuns(): array
    {
        $monitoramento = ['tipo' => TipoOperacao::TratosCulturais, 'descricao' => 'Monitoramento de pragas e doenças', 'funcionarios' => ['Luiz Fernando Alves' => 0.2, 'Antônio Ferreira' => 0.3]];

        return [
            [...$monitoramento, 'data' => '2025-10-28'],
            [...$monitoramento, 'data' => '2025-11-26'],
            [...$monitoramento, 'data' => '2025-12-18'],
            [...$monitoramento, 'data' => '2026-01-27'],
            [...$monitoramento, 'data' => '2026-02-12'],
            ['data' => '2026-05-12', 'tipo' => TipoOperacao::Pulverizacao, 'descricao' => 'Manejo de plantas daninhas na entressafra', 'insumos' => ['Glifosato' => 2.0, 'Óleo mineral (adjuvante)' => 0.5], 'maquinas' => ['Pulverizador Jacto Uniport 3030' => 0.25], 'funcionarios' => ['Carlos Henrique Souza' => 0.25]],
            ['data' => '2026-06-16', 'tipo' => TipoOperacao::TratosCulturais, 'descricao' => 'Manutenção de carreadores e terraços', 'maquinas' => ['Trator Massey Ferguson 4707' => 0.3], 'funcionarios' => ['Marcos Vinícius Rocha' => 0.3, 'Antônio Ferreira' => 0.5], 'implementos' => ['Grade aradora intermediária']],
            ['data' => '2026-07-14', 'tipo' => TipoOperacao::TratosCulturais, 'descricao' => 'Amostragem de solo para a próxima safra', 'funcionarios' => ['Luiz Fernando Alves' => 0.2]],
        ];
    }
}
