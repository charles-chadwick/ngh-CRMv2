<?php

namespace App\Http\Requests;

use App\Enum\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $user = $this->route('user');
        $is_update = $user !== null;

        return [
            'role' => [
                'required',
                Rule::enum(UserRole::class),
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
            'email' => [
                'required',
                'email',
                'max:255',
                $is_update
                    ? Rule::unique('users', 'email')
                        ->ignore($user->id)
                    : 'unique:users,email',
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
