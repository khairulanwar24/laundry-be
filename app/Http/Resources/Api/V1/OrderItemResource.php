<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'service_variant_id' => $this->service_variant_id,
            'name' => $this->variant?->name,
            'unit' => $this->variant?->unit,
            'qty' => $this->qty,
            'price_per_unit_snapshot' => $this->price_per_unit_snapshot,
            'line_total' => $this->line_total,
            'note' => $this->note,
        ];
    }
}
