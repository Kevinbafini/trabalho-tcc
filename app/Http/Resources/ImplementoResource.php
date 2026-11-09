<?php

namespace App\Http\Resources;

use App\Models\Implemento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Implemento: não tem custo próprio, apenas é associado às atividades.
 *
 * @mixin Implemento
 */
class ImplementoResource extends JsonResource
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
            'propriedade_id' => $this->propriedade_id,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'modelo' => $this->modelo,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
