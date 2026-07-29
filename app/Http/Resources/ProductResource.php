<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrls = collect($this->images ?? [])->map(function ($path) {
            return Storage::disk('public')->url($path);
        });

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'care_guide'     => $this->care_guide,
            'price'          => $this->price,
            'discount_price' => $this->discount_price,
            'stock'          => $this->stock,
            'in_stock'       => $this->stock > 0,
            'images'         => $imageUrls,
            'is_active'      => $this->is_active,
            'store'          => new StoreResource($this->whenLoaded('store')),
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'attributes'     => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues->map(fn ($val) => [
                    'attribute_id' => $val->attribute_id,
                    'value_id'     => $val->id,
                    'value'        => $val->value,
                ]);
            }),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}