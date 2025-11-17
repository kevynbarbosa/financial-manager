<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportOfxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ofx_file' => ['required', 'file', 'mimes:ofx,txt', 'max:4096'],
        ];
    }
}
