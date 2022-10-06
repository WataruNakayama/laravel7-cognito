<?php

namespace App\Validators;

use App\Services\Cognito\CognitoClient;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;


class CognitoUserEmailUniqueValidator {
    /**
     * Cognito上に存在するユーザーか
     * @throws BindingResolutionException
     */
    public function validate($attribute, $value, $parameters, $validator): bool
    {
        $isCognitoUser = false;

        try {
            // Cognitoからユーザーを取得
            $isCognitoUser = (bool)app()
                ->make(CognitoClient::class)
                ->getCognitoUser($value);
        } catch (Exception $e) {
            // エラーは握り潰す
        }

        return !$isCognitoUser;
    }
}
