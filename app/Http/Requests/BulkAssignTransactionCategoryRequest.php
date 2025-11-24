<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAssignTransactionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $userId = $this->user()?->id ?? 0;

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('transaction_categories', 'id')->where('user_id', $userId),
            ],
            'match_type' => ['required', 'in:exact,contains'],
            'term' => ['required', 'string', 'max:255'],
            'overwrite_existing' => ['sometimes', 'boolean'],
        ];
    }
}
