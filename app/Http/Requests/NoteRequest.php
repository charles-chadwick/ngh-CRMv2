<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteRequest extends FormRequest
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
        $is_update = $this->route('note') !== null;

        return [
            'model_type' => [
                Rule::requiredIf(! $is_update),
                'string',
                Rule::in(['customer', 'company', 'lead']),
            ],
            'model_id' => [
                Rule::requiredIf(! $is_update),
                'integer',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Note::TYPES),
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
            ],
        ];
    }
}
