<?php

namespace App\Http\Resources;

use App\Models\Maquina;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Maquina
 */
class MaquinaResource extends JsonResource
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
            'ano' => $this->ano,
            'custo_hora' => $this->custo_hora,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
