<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
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
            'warehouse_name' => 'required|string',
            'warehouse_address_1' => 'required|string',
            'city' => 'required|string',
            'state_or_province_code' => 'required|string',
            'country_code' => 'required|string',
            'postal_code' => 'required|string',
        ];
    }

}
