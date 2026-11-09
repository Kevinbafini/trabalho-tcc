<?php

namespace App\Http\Resources;

use App\Models\Talhao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Talhao
 */
class TalhaoResource extends JsonResource
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
            'propriedade' => $this->whenLoaded('propriedade', fn (): array => [
                'id' => $this->propriedade->id,
                'nome' => $this->propriedade->nome,
            ]),
            'nome' => $this->nome,
            'area' => $this->area,
            'cultura' => $this->cultura,
            'safra' => $this->safra,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
