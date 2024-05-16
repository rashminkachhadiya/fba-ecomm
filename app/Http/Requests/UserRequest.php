<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            $emailValidation = [];
            if(isset($this->email))
            {
                $emailValidation = [
                    ...$emailValidation,
                    'email'=> 'required|email|unique:users,email,'.$this->id.',id',
                ];
            }

            return [
                'name' => 'required|max:16',
                ...$emailValidation
            ];
        }else{
            return [
                'name' => 'required|max:16',
                'email'=> 'required|email|unique:users,email,NULL',
                'password' => 'required|min:8|max:16',
                'password_confirmation' => 'required|same:password'
            ];
        }
    }
}
