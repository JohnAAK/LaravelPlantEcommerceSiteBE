<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_number'     => $this->order_number,
            'subtotal'         => $this->subtotal,
            'delivery_fee'     => $this->delivery_fee,
            'total_amount'     => $this->total_amount,
            'payment_method'   => $this->payment_method,
            'payment_status'   => $this->payment_status,
            'status'           => $this->status,
            'shipping_details' => [
                'name'    => $this->shipping_name,
                'phone'   => $this->shipping_phone,
                'address' => $this->shipping_address,
                'city'    => $this->city,
                'notes'   => $this->notes,
            ],
            // Include vendor details if this is a vendor sub-order
            'store' => $this->when($this->store_id, function () {
                return [
                    'id'   => $this->store->id,
                    'name' => $this->store->name,
                    'slug' => $this->store->slug,
                ];
            }),
            // Child vendor sub-orders (for parent orders)
            'sub_orders' => OrderResource::collection($this->whenLoaded('subOrders')),
            // Order line items
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'unit_price'   => $item->unit_price,
                    'quantity'     => $item->quantity,
                    'total'        => $item->total,
                ]);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}