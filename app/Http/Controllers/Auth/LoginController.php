<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Exception;
use Illuminate\Support\Facades\Auth;
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
        try
        {
            $credentials = $request->only(["email", "password"]);

            // ログイン実行
            $result = Auth::guard("web")->loginByDb($credentials);

            if (!$result) {
                // ログインできなかった場合
                throw ValidationException::withMessages([
                    $this->username() => "Login Failed.",
                ]);
            }

            /** @var $user array Cognitoの情報 */
            $cognito = $result["cognito"];
            $auth = $cognito["auth"]["AuthenticationResult"];

            /** cookieに保持させる値 */
            $addCookieStr = collect([
                // ユーザー種別
                "type" => "user",
                // Cognito上のUsername
                "username" => $cognito["user"]["Username"],
                // IDトークン（主に認可用に引き回すトークン）
                "id_token" => $auth["IdToken"],
                // アクセストークン（emailやpasswordの属性更新などのセキュアな処理を呼ぶときに必要になるトークン）
                "access_token" => $auth["AccessToken"],
                // リフレッシュトークン
                "refresh_token" => $auth["RefreshToken"],
            ])->toJson();

            $cookie = cookie()->forever("laravel-cognito", $addCookieStr);
            cookie()->queue($cookie);

            // 最新のlogin_tokenでcookieを更新する
            return redirect("/home");
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

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        return redirect('/')->withCookie(cookie()->forget("laravel-cognito"));
    }

}
