<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
        if (request('contact_info_id')) {
            return [
                'name' => 'required|regex:/^[^\s].*$/',
                'email' => 'required|email|unique:supplier_contacts,email,' . request('contact_info_id') . ',id',
                'phone_number' => 'nullable|numeric',
            ];
        } else {
            return [
                'name' => 'required|regex:/^[^\s].*$/',
                'email' => 'required|email|unique:supplier_contacts,email,NULL',
                'phone_number' => 'nullable|numeric',
            ];
        }
    }

    public function messages()
    {
        return [
            'regex' => 'Contact name cannot contain spaces'
        ];
    }
}
