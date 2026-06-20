<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
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
        $is_update = $this->route('contact') !== null;

        return [
            'model_type' => [
                Rule::requiredIf(! $is_update),
                'string',
                Rule::in(['customer', 'company']),
            ],
            'model_id' => [
                Rule::requiredIf(! $is_update),
                'integer',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Contact::TYPES),
            ],
            'is_primary' => [
                'boolean',
            ],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'work_phone' => ['nullable', 'string', 'max:255'],
            'mobile_phone' => ['nullable', 'string', 'max:255'],
        ];
    }
}
