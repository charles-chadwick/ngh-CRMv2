<?php

namespace App\Http\Requests;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                Rule::enum(LeadType::class),
            ],
            'status' => [
                'required',
                Rule::enum(LeadStatus::class),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'initiated_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
