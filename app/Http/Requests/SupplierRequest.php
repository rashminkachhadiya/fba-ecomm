<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
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
        if ($this->id) {
            return [
                'name' => 'required|regex:/^[^\s].*$/',
                'email' => 'required|email|unique:suppliers,email,' . $this->id . ',id',
                'url' => 'nullable|regex:/^(https?:\/\/)?(www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(\/[^\s]*)?$/i',
                'lead_time' => 'required|integer',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|numeric',
                'account_number' => 'nullable|numeric',
            ];
        } else {
            return [
                'name' => 'required|regex:/^[^\s].*$/',
                'email' => 'required|email|unique:suppliers,email,NULL',
                'url' => 'nullable|regex:/^(https?:\/\/)?(www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(\/[^\s]*)?$/i',
                'lead_time' => 'required|integer',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|numeric',
                'account_number' => 'nullable|numeric',
            ];
        }
    }

    public function messages()
    {
        return [
            'name.regex' => 'Supplier name cannot contain spaces',
            'url.regex' => 'Invalid website url, please enter a valid url',
        ];
    }

}
