<?php

namespace App\Models;

use App\Enums\PapelMensagem;
use App\Enums\ProvedorIa;
use Database\Factories\MensagemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Mensagem de uma conversa com o Assistente IA (do usuário ou da IA).
 *
 * @property int $id
 * @property int $conversa_id
 * @property PapelMensagem $papel
 * @property string $conteudo
 * @property ProvedorIa|null $provedor
 * @property string|null $modelo
 * @property list<string>|null $ferramentas nomes das ferramentas usadas na resposta
 * @property int $tokens_entrada
 * @property int $tokens_saida
 * @property int $tokens_cache
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $conteudo_html
 */
#[Table('mensagens')]
#[Fillable(['papel', 'conteudo', 'provedor', 'modelo', 'ferramentas', 'tokens_entrada', 'tokens_saida', 'tokens_cache'])]
class Mensagem extends Model
{
    /** @use HasFactory<MensagemFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'tokens_entrada' => 0,
        'tokens_saida' => 0,
        'tokens_cache' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'papel' => PapelMensagem::class,
            'provedor' => ProvedorIa::class,
            'ferramentas' => 'array',
            'tokens_entrada' => 'integer',
            'tokens_saida' => 'integer',
            'tokens_cache' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Conversa, $this>
     */
    public function conversa(): BelongsTo
    {
        return $this->belongsTo(Conversa::class);
    }

    /**
     * Conteúdo pronto para exibir: o texto do usuário escapado e a resposta
     * da IA convertida de Markdown para HTML seguro.
     *
     * @return Attribute<string, never>
     */
    protected function conteudoHtml(): Attribute
    {
        return Attribute::get(fn (): string => $this->papel === PapelMensagem::Assistente
            ? (string) self::conversorDeMarkdown()->convert($this->conteudo)
            : nl2br(e($this->conteudo)));
    }

    /**
     * Markdown das respostas da IA, sem HTML e sem imagens.
     *
     * HTML digitado é removido e links inseguros (javascript:) são descartados.
     * Imagem nunca vira <img>: o navegador buscaria a URL sozinho, sem clique,
     * e uma resposta induzida por texto plantado nos cadastros (prompt
     * injection) poderia vazar dados pela URL. Cada imagem vira só o texto.
     */
    private static function conversorDeMarkdown(): GithubFlavoredMarkdownConverter
    {
        $conversor = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $conversor->getEnvironment()->addEventListener(
            DocumentParsedEvent::class,
            static function (DocumentParsedEvent $evento): void {
                $imagens = [];

                foreach ($evento->getDocument()->iterator() as $no) {
                    if ($no instanceof Image) {
                        $imagens[] = $no;
                    }
                }

                foreach ($imagens as $imagem) {
                    foreach ($imagem->children() as $textoAlternativo) {
                        $imagem->insertBefore($textoAlternativo);
                    }

                    $imagem->detach();
                }
            },
        );

        return $conversor;
    }
}
