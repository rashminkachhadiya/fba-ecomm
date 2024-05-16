<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FBATransportInfoRequest extends FormRequest
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
        $carrierType = $this->input('shipping_carrier_type');
        $shippingMethod = $this->input('shipping_method_radio');

        if($carrierType == '0' && $shippingMethod == 'LTL' && $this->input('pro_number') == null){
            $pro_number = 'required|min:4';
        }else{
            $pro_number = 'nullable';
        }

        return [
            'pro_number' => $pro_number,
        ];
    }
}
