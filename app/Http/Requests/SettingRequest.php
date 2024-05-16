<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
            'supplier_lead_time' => 'required|integer|min:0',
            'day_stock_holdings' => 'required|integer|min:0',
            'shipping_address' => 'required|string',
            'company_address' => 'required|string',
            'company_phone' => 'required|regex:/^\+[0-9]{10,12}$/',
            'company_email' => 'required|email',
            'warehouse_address' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'company_phone.regex' => 'Phone number must be start with + and contain 10-12 digits',
        ];
    }
}
