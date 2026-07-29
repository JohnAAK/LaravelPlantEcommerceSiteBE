<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Store;

class ApproveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status'           => ['required', Rule::in([Store::STATUS_APPROVED, Store::STATUS_REJECTED])],
            'rejection_reason' => ['required_if:status,' . Store::STATUS_REJECTED, 'nullable', 'string', 'max:1000'],
        ];
    }
}