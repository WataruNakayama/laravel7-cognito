<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        try
        {
            if ($this->attemptLogin($request)) {
                return $this->sendLoginResponse($request);
            }
        } catch (CognitoIdentityProviderException $c) {
            return $this->sendFailedCognitoResponse($c);
        } catch (Exception $e) {
            return $this->sendFailedLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    private function sendFailedCognitoResponse(CognitoIdentityProviderException $exception)
    {
        throw ValidationException::withMessages([
            $this->username() => $exception->getAwsErrorMessage(),
        ]);
    }
}
