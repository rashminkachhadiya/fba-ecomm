<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserProfileUpdateRequest extends FormRequest
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
                'name' => 'required|max:16',
                'email'=> 'required|email|unique:users,email,'.auth()->user()->id,
                'password' => 'required|min:8|max:16',
                'password_confirmation' => 'required|same:password'
            ];
    
    }
}
