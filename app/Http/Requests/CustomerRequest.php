<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
        $customer = $this->route('customer');
        $is_update = $customer !== null;

        return [
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'role' => [
                'nullable',
                'string',
                'max:255',
            ],
            'prefix' => [
                'nullable',
                'string',
                'max:15',
            ],
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'suffix' => [
                'nullable',
                'string',
                'max:15',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                $is_update
                    ? Rule::unique('customers', 'email')
                        ->ignore($customer->id)
                    : 'unique:customers,email',
            ],
            'password' => [
                $is_update ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
