<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'slug'   => $this->slug,
            'values' => $this->whenLoaded('values', function () {
                return $this->values->map(fn ($val) => [
                    'id'    => $val->id,
                    'value' => $val->value,
                    'slug'  => $val->slug,
                ]);
            }),
        ];
    }
}