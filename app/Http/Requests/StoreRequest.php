<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        if($this->id)
        {
            $storeNameValidation = 'required|regex:/^[^\s].*$/|unique:stores,store_name,'.$this->id.',id';
        }else{
            $storeNameValidation = 'required|regex:/^[^\s].*$/|unique:stores,store_name';
        }

        return [
            // 'store_name' => 'required|regex:/^[^\s]+$/',
            'store_name' => $storeNameValidation,
            'store_config_id' => 'required',
            'client_id' => 'required|regex:/^[^\s].*$/',
            'client_secret' => 'required|regex:/^[^\s].*$/',
            'aws_access_key_id' => 'required|regex:/^[^\s].*$/',
            'aws_secret_key' => 'required|regex:/^[^\s].*$/',
            'refresh_token' => 'required|regex:/^[^\s].*$/'
        ];
    }

    public function messages()
    {
        return [
            'store_name.regex' => 'Store name cannot begin with a space.',
            'client_id.regex' => 'Client ID cannot begin with a space.',
            'client_secret.regex' => 'Client Secret cannot begin with a space.',
            'aws_access_key_id.regex' => 'AWS Access Key ID cannot begin with a space.',
            'aws_secret_key.regex' => 'AWS Secret Key cannot begin with a space.',
            'refresh_token.regex' => 'Refresh Token cannot begin with a space.',
        ];
    }
}
