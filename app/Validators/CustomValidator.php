<?php

namespace App\Validators;

use Illuminate\Validation\Validator;

class CustomValidator extends Validator
{
    /**
     * パスワードが半角英数記号を1文字ずつ含むかチェック
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateFormatPassword($attribute, $value, $parameters): bool
    {
        $hasAlphabet = preg_match('/[a-zA-Z]/', $value);
        $hasNumber = preg_match('/\d/', $value);
        $hasSymbol = preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $value);
        return !empty($value) && $hasAlphabet && $hasNumber && $hasSymbol;
    }
}
