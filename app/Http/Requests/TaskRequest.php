<?php

namespace App\Http\Requests;

use App\Enum\TaskType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['nullable', 'string', Rule::in(['customer', 'company', 'lead'])],
            'subject_id' => ['nullable', 'integer', 'required_with:subject_type'],
            'assignee_type' => ['nullable', 'string', Rule::in(['user', 'customer'])],
            'assignee_id' => ['nullable', 'integer', 'required_with:assignee_type'],
            'type' => ['required', 'string', Rule::in(array_column(TaskType::cases(), 'value'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'all_day' => ['boolean'],
            'remind_at' => ['nullable', 'date'],
        ];
    }
}
