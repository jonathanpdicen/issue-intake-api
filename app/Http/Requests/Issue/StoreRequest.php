<?php

namespace App\Http\Requests\Issue;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                'max:5000',
            ],
            'priority' => [
                'required',
                Rule::enum(IssuePriority::class),
            ],
            'category' => [
                'required',
                'string',
                'max:100',
            ],
            'status' => [
                'filled',
                Rule::enum(IssueStatus::class),
            ],
        ];
    }
}
