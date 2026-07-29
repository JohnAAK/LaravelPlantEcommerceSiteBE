<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'location'         => $this->location,
            'logo_url'         => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'banner_url'       => $this->banner ? Storage::disk('public')->url($this->banner) : null,
            'contact_info'     => $this->contact_info,
            'status'           => $this->status,
            'rejection_reason' => $this->when($request->user()?->isAdmin() || $request->user()?->id === $this->user_id, $this->rejection_reason),
            'owner'            => new UserResource($this->whenLoaded('owner')),
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}