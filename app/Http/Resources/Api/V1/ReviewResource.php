<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            'user'       => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ],
            'product'    => $this->whenLoaded('product', fn() => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}