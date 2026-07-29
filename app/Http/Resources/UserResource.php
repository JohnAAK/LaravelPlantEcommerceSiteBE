<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
  public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'role'       => $this->role,
            'has_store'  => $this->whenLoaded('store', fn() => $this->store !== null, $this->store()->exists()),
            'store_id'   => $this->when($this->relationLoaded('store') && $this->store, fn() => $this->store?->id),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
