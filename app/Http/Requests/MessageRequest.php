<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $is_update = $this->route('message') !== null;

        return [
            'model_type' => [
                Rule::requiredIf(! $is_update),
                'string',
                Rule::in(['customer']),
            ],
            'model_id' => [
                Rule::requiredIf(! $is_update),
                'integer',
            ],
            'content' => [
                'required',
                'string',
            ],
        ];
    }
}
