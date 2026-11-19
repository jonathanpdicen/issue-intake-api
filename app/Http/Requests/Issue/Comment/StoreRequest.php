<?php

namespace App\Http\Requests\Issue\Comment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => [
                'required',
                'string',
                'max:100',
            ],
            'body' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }
}
