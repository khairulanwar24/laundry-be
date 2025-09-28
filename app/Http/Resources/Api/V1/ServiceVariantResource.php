<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceVariantResource extends JsonResource
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
            'name' => $this->name,
            'unit' => $this->unit,
            'price_per_unit' => $this->price_per_unit,
            'tat_duration_hours' => $this->tat_duration_hours,
            'image_path' => $this->image_path,
            'note' => $this->note,
            'is_active' => $this->is_active,
        ];
    }
}
