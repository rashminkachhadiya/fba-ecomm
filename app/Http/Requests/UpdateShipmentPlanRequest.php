<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\EditSellableUnitRule;

class UpdateShipmentPlanRequest extends FormRequest
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
            'plan_name' => 'required|unique:shipment_plans,plan_name,' . $this->id . ',id',
            'po_id' => 'nullable|integer',
            'sellable_unit.*' => ['required', 'integer', 'min:1', new EditSellableUnitRule($this->input('pack_of'), $this->input('wh_qty'), $this->input('old_sellable_unit'))],
        ];
    }

    public function messages(): array
    {
        return [
            'sellable_unit.*.integer' => 'The sellable unit must be an integer.',
            'sellable_unit.*.min' => 'The sellable unit must be greate than 0'
        ];
    }
}
