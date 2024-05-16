<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'unit_price.*' => 'numeric|min:0.1',
        ];
    }

    public function messages(): array
    {
        return [
            'unit_price.*.numeric' => 'The unit_price field must be a number.',
            'unit_price.*.min' => 'The unit_price field must be at least 0.1.'
        ];
    }
}
