<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepItemRequest extends FormRequest
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
            'number_of_label' => 'required',
            'expire_date' => 'nullable|date_format:m/d/Y|after_or_equal:item_max_expiry_date',
        ];
    }

    public function messages()
    {
        return [
            'number_of_label.required' => "Items labels to print field is required",
            'expire_date.after_or_equal' => "The expiration date must be after or equal to " . $this->item_max_expiry_date. " date.",
        ];
    }
}
