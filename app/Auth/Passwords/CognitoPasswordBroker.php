<?php

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use UnexpectedValueException;
use App\Auth\CognitoUserProvider;

class CognitoPasswordBroker extends PasswordBroker
{
    /**
     * Create a new password broker instance.
     *
     * @param  \Illuminate\Auth\Passwords\TokenRepositoryInterface  $tokens
     * @param  CognitoUserProvider $users
     * @return void
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct(TokenRepositoryInterface $tokens, CognitoUserProvider $users)
    {
        $this->users = $users;
        $this->tokens = $tokens;
    }

    /**
     * Get the user for the given credentials.
     *
     * @param  array  $credentials
     * @return CanResetPassword|null
     *
     * @throws UnexpectedValueException
     */
    public function getUser(array $credentials): ?CanResetPassword
    {
        $credentials = Arr::except($credentials, ['token']);

        $result = $this->users->retrieveByEmail($credentials);
        $user = $result["model"] ?? null;

        if ($user && ! $user instanceof CanResetPassword) {
            throw new UnexpectedValueException('User must implement CanResetPassword interface.');
        }

        return $user;
    }

}
