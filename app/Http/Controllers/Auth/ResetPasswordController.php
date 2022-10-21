<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Cognito\CognitoClient;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Reset the given user's password.
     *
     * @param User $user
     * @param string $password
     * @return void
     * @throws BindingResolutionException
     */
    protected function resetPassword($user, $password): void
    {
        $this->setUserPassword($user, $password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        // Cognito上のパスワードを変更
        app()
            ->make(CognitoClient::class)
            ->adminSetUserPassword($user->email, $password);

        event(new PasswordReset($user));

        $this->guard()->login($user);
    }
}
