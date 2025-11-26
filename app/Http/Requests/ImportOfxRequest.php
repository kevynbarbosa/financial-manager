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
            'ofx_file' => [
                'required',
                'file',
                'extensions:ofx,txt',
                'mimetypes:text/plain,application/xml,text/xml,application/octet-stream,application/ofx,application/x-ofx,text/x-ofx,text/ofx',
                'max:4096',
            ],
        ];
    }
}
