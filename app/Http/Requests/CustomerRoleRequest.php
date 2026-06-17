<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRoleRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $customer_role = $this->route('customer_role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customer_roles', 'name')
                    ->ignore($customer_role?->id),
            ],
        ];
    }
}
