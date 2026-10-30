<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BuscarCadastrosTool;
use App\Mcp\Tools\CadastrarFuncionarioTool;
use App\Mcp\Tools\CadastrarImplementoTool;
use App\Mcp\Tools\CadastrarInsumoTool;
use App\Mcp\Tools\CadastrarMaquinaTool;
use App\Mcp\Tools\CadastrarPropriedadeTool;
use App\Mcp\Tools\CadastrarTalhaoTool;
use App\Mcp\Tools\ConsultarCustosTool;
use App\Mcp\Tools\Ferramenta;
use App\Mcp\Tools\ListarAtividadesTool;
use App\Mcp\Tools\RegistrarAtividadeTool;
use App\Mcp\Tools\ResumoGeralTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Servidor MCP do sistema: o mesmo conjunto de ferramentas atende
 *
 *   - o Assistente IA do próprio sistema (o chat chama as ferramentas
 *     diretamente, sem passar pela rede — ver App\Services\Ia\FerramentasDoAssistente);
 *   - apps de IA externos (Claude, ChatGPT...) conectados em /mcp com OAuth
 *     (routes/ai.php).
 *
 * As ferramentas só consultam e cadastram; nenhuma edita ou exclui registros.
 */
#[Name('Sistema de Controle de Atividades Agrícolas')]
#[Version('1.0.0')]
#[Instructions('Ferramentas para consultar e cadastrar dados de propriedades rurais: propriedades, talhões, funcionários, máquinas, implementos, insumos e atividades agrícolas, com custos apropriados por talhão. Use buscar-cadastros para obter IDs a partir de nomes, sem nunca inventar IDs. Toda ferramenta de cadastro funciona em dois passos: primeiro com confirmado_pelo_usuario=false (prévia, nada é salvo); mostre a prévia, peça confirmação explícita e só então repita com confirmado_pelo_usuario=true. Os custos são calculados pelo sistema; não refaça contas.')]
class SistemaAgricolaServer extends Server
{
    /**
     * Ferramentas do servidor, em ordem alfabética do nome (uma ordem fixa
     * ajuda o cache de prompt dos provedores de IA).
     *
     * @var list<class-string<Ferramenta>>
     */
    public const FERRAMENTAS = [
        BuscarCadastrosTool::class,
        CadastrarFuncionarioTool::class,
        CadastrarImplementoTool::class,
        CadastrarInsumoTool::class,
        CadastrarMaquinaTool::class,
        CadastrarPropriedadeTool::class,
        CadastrarTalhaoTool::class,
        ConsultarCustosTool::class,
        ListarAtividadesTool::class,
        RegistrarAtividadeTool::class,
        ResumoGeralTool::class,
    ];

    protected array $tools = self::FERRAMENTAS;

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
