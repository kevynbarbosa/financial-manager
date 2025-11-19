<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'is_transfer' => ['nullable', 'boolean'],
        ];
    }
}
