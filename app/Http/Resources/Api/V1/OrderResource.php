<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'outlet_id' => $this->outlet_id,
            'customer_id' => $this->customer_id,
            'invoice_no' => $this->invoice_no,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method_id' => $this->payment_method_id,
            'perfume_id' => $this->perfume_id,
            'discount_id' => $this->discount_id,
            'discount_value_snapshot' => $this->discount_value_snapshot,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'notes' => $this->notes,
            'checkin_at' => $this->checkin_at,
            'eta_at' => $this->eta_at,
            'finished_at' => $this->finished_at,
            'canceled_at' => $this->canceled_at,
            'collected_at' => $this->collected_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
