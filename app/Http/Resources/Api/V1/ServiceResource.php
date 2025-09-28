<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'priority_score' => $this->priority_score,
            'process_steps' => $this->process_steps_json,
            'is_active' => $this->is_active,
            'variants' => $this->when(
                $this->relationLoaded('serviceVariants'),
                ServiceVariantResource::collection($this->serviceVariants->where('is_active', 1)),
                ServiceVariantResource::collection($this->serviceVariants()->where('is_active', 1)->get())
            ),
        ];
    }
}
