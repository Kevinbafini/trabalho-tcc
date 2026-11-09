<?php

namespace App\Http\Resources;

use App\Models\Propriedade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Propriedade
 */
class PropriedadeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'proprietario' => $this->proprietario,
            'municipio' => $this->municipio,
            'uf' => $this->uf,
            'area_total' => $this->area_total,
            'observacoes' => $this->observacoes,
            'demonstracao' => $this->demonstracao,
            'quantidade_talhoes' => $this->whenCounted('talhoes'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
