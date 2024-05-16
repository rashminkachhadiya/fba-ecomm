<?php

namespace App\Http\Requests;

use App\Rules\SellableUnitRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentPlanRequest extends FormRequest
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
        $poID = $this->po_id;
        if(!empty($poID)) {
            $sellableUnitRules = ['integer', 'min:1', new SellableUnitRule($this->input('pack_of'), $this->input('wh_qty'))];
            $storeRule = 'required|integer';
        }else{
            $sellableUnitRules = ['required', 'integer', 'min:1', new SellableUnitRule($this->input('pack_of'), $this->input('wh_qty'))];
            $storeRule = 'nullable|integer';
        }
        return [
            'store_id' => $storeRule,
            'warehouse_id' => 'required|integer',
            'plan_name' => 'required|unique:shipment_plans,plan_name',
            'prep_preference' => 'required',
            'destination_country' => 'required',
            'box_content' => 'required',
            'packing_details' => 'required',
            'po_id' => 'nullable|integer',
            // 'sellable_unit.*' => 'integer|min:1'
            'sellable_unit.*' => $sellableUnitRules
        ];
    }

    public function messages(): array
    {
        return [
            'sellable_unit.*.integer' => 'The sellable unit must be an integer.',
            'sellable_unit.*.min' => 'The sellable unit must be greate than 0'
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         foreach ($this->input('sellable_unit') as $index => $sellableQty)
    //         {
    //             $packOf = $this->input("pack_of.$index");
    //             $whQty = $this->input("wh_qty.$index");
                
    //             if (($packOf * $sellableQty) > $whQty) {
    //                 $validator->errors()->add("sellable_unit.$index", "The sellable unit must be less than or equal to ware house qty.");
    //             }
    //         }
    //     });
    // }
}
