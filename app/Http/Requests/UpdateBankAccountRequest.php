<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'account_type' => ['required', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:191'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}
