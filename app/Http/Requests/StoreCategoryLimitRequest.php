<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_category_id' => [
                'required',
                'integer',
                Rule::exists('transaction_categories', 'id')->where('user_id', $this->user()?->id),
            ],
            'monthly_limit' => ['required', 'numeric', 'min:0'],
        ];
    }
}
