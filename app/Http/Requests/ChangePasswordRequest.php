<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "password" => "required|string|min:8|max:50|format_password",
            "password_confirmation" => "required|string|min:8|max:50|format_password",
        ];
    }
}
