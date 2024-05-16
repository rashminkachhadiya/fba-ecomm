<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockRequest extends FormRequest
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
        if (isset($this->purchase_order_id) && $this->purchase_order_id != 0) {
            return [
                'po_number' => 'required|regex:/^[^\s].*$/|unique:purchase_orders,po_number,' . $this->purchase_order_id . ',id',
                'po_order_date' => 'required|date_format:m-d-Y',
                'expected_delivery_date'  => 'nullable|date_format:m-d-Y|after_or_equal:po_order_date',
            ];
        } else {
            return [
                'po_number' => 'required|regex:/^[^\s].*$/|unique:purchase_orders,po_number,NULL',
                'po_order_date' => 'required|date_format:m-d-Y',
                'expected_delivery_date'  => 'nullable|date_format:m-d-Y|after_or_equal:po_order_date',
            ];
        }
    }

    public function messages()
    {
        return [
            'regex' => 'po number cannot contain spaces',
        ];
    }
}
