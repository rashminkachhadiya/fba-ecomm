<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Factory as ValidationFactory;

class RestockProductFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function __construct(ValidationFactory $validationFactory)
    {
        $validationFactory->extend('valid_min_max_qty', function ($attribute, $value, $parameters, $validator) {
            $minQty = $validator->getData()[$parameters[0]];
            $maxQty = $value;
        
            if ($minQty !== null && $maxQty !== null && $minQty > $maxQty) {
                return false;
            }
        
            return true;
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // return [
        //     'min_fba_qty' => 'nullable|integer',
        //     'max_fba_qty' => 'nullable|integer',
        //     'min_inbound_qty' => 'nullable|integer',
        //     'max_inbound_qty' => 'nullable|integer',
        //     'min_suggested_qty' => 'nullable|integer',
        //     'max_suggested_qty' => 'nullable|integer' ,
        //     'min_price' => 'nullable|numeric',
        //     'max_price' => 'nullable|numeric',
        //     'min_buybox_price' => 'nullable|numeric',
        //     'max_buybox_price' => 'nullable|numeric',
        //     'min_selling_price' => 'nullable|numeric',
        //     'max_selling_price' => 'nullable|numeric',
        // ];

        $rules = [
            'min_fba_qty' => 'nullable|integer',
            'max_fba_qty' => 'nullable|integer|valid_min_max_qty:min_fba_qty',
            // 'max_fba_qty' => 'nullable|integer|gte:min_fba_qty',
            'min_inbound_qty' => 'nullable|integer',
            'max_inbound_qty' => 'nullable|integer',
            'min_suggested_qty' => 'nullable|integer',
            'max_suggested_qty' => 'nullable|integer',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_buybox_price' => 'nullable|numeric',
            'max_buybox_price' => 'nullable|numeric',
            'min_selling_price' => 'nullable|numeric',
            'max_selling_price' => 'nullable|numeric',
        ];
    
        // Add the 'gte' rule conditionally if both min and max values are provided
        // if ($this->input('min_fba_qty') !== null && $this->input('max_fba_qty') !== null) {
        //     $rules['max_fba_qty'] .= '|gte:min_fba_qty';
        // }

        return $rules;
    }

    public function messages()
    {
        return [
            'max_fba_qty.valid_min_max_qty' => "The max fba qty field must be greater than or equal to min fba qty.",
        ];
    }
}
