<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()->store()->exists();
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255', 'unique:stores,name'],
            'description'  => ['nullable', 'string'],
            'location'     => ['required', 'string', 'max:255'],
            'logo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'contact_info' => ['nullable', 'array'],
        ];
    }
}