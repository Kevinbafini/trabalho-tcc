<?php

namespace App\Http\Resources;

use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Insumo
 */
class InsumoResource extends JsonResource
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
            'categoria' => $this->categoria->value,
            'categoria_rotulo' => $this->categoria->rotulo(),
            'unidade' => $this->unidade->value,
            'unidade_rotulo' => $this->unidade->rotulo(),
            'valor_unitario' => $this->valor_unitario,
            'quantidade_estoque' => $this->quantidade_estoque,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
