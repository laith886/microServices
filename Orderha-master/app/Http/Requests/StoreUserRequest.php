<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:10|min:10',
            'password' => 'required|string|min:8 ',
            'profile_image' => 'nullable|image|max:2048',
            'location' => 'nullable|string|max:255',
        ];
    }
}
