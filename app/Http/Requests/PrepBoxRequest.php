<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Factory as ValidationFactory;

class PrepBoxRequest extends FormRequest
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
        $validationFactory->extend('total_qty_value', function ($attribute, $value, $parameters, $validator) {
            if (!empty($value) && array_sum($value) > 0) {
                $data = $validator->getData();
                
                $param = isset($parameters[0]) ? $parameters[0] : 'box_lbl_qty';

                $paramValue = isset($data[$param]) ? $data[$param] : 0;

                $percentageValue = floor((config('constants.SHIPMENT_ASIN_QTY_PERCENT') / 100) * $paramValue);

                $shipmentMaxQty = config('constants.SHIPMENT_ASIN_QTY_MAX');

                if ($percentageValue > $shipmentMaxQty) {
                    $newShipmentQty = $paramValue + $percentageValue;
                } else {
                    $newShipmentQty = $paramValue + $shipmentMaxQty;
                }

                if(array_sum($value) > $newShipmentQty) {
                    return false;
                }

                return true;
            }
            return false;
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'asin_weight' => 'required',
            'per_box_item_count' => 'required',
            'no_of_boxes_count' => 'required',
            'box_lbl_qty' => 'required',
            'tot_qty' => 'array|total_qty_value:box_lbl_qty',
            'expiry_box_date.*' => 'nullable|after_or_equal:max_expiry_date',
        ];
    }

    public function messages()
    {
        return [
            'asin_weight.required' => "ASIN Weight field is required",
            'per_box_item_count.required' => "Item per box field is required",
            'no_of_boxes_count.required' => "Number of boxes field is required",
            'tot_qty.total_qty_value' => "Total box quantity value increase only 5 percent or 6 quantity.",
            'expiry_box_date.*.after_or_equal' => "The expiration date must be after or equal to " . $this->max_expiry_date. " date.",
        ];
    }
}
