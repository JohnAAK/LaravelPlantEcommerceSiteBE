<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user->isVendor() && $user->store && $user->store->status === 'approved';
    }

    public function rules(): array
    {
        return [
            'category_id'      => ['required', 'exists:categories,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'care_guide'       => ['nullable', 'string'],
            'price'            => ['required', 'numeric', 'min:0'],
            'discount_price'   => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock'            => ['required', 'integer', 'min:0'],
            'images'           => ['required', 'array', 'min:1', 'max:5'],
            'images.*'         => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'], // Max 3MB per image
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => ['exists:attribute_values,id'],
        ];
    }
}