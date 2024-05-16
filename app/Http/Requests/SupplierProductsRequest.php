<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierProductsRequest extends FormRequest
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
            'unit_price.*' => 'nullable|numeric|min:0',
            'additional_cost.*' => 'nullable|numeric|min:0',
            'order_qty.*' => 'nullable|integer|min:0',
            'supplier_sku.*' => 'nullable|max:30',
        ];
    }

    public function messages()
    {
        return [
            'unit_price.*.numeric' => 'Unit price must be a number.',
            'additional_cost.*.numeric' => 'Additional cost must be a number.',
            'unit_price.*.min' => 'Unit price must be greater than or equal to 0.',
            'additional_cost.*.min' => 'Additional cost must be greater than or equal to 0.',
            'order_qty.*.min' => 'Order quantity must be greater than or equal to 0.',
            'order_qty.*.integer' => 'Order quantity must be an integer.',
            'supplier_sku.*.max' => 'Supplier SKU must be less than or equal to 30 characters.',
        ];
    }
}
