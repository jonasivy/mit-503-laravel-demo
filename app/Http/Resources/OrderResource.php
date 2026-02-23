<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Design: API Resource for consistent JSON response shape.
 *
 * Transforms the Order model into a standardized JSON structure,
 * ensuring the API response format is decoupled from the database schema.
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'customer_name'  => $this->customer_name,
            'customer_email' => $this->customer_email,
            'item'           => $this->item,
            'quantity'        => $this->quantity,
            'status'         => $this->status,
            'total_price'    => $this->total_price,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
