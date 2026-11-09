<?php

namespace App\Http\Resources;

use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Funcionario
 */
class FuncionarioResource extends JsonResource
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
            'funcao' => $this->funcao,
            'custo_hora' => $this->custo_hora,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
