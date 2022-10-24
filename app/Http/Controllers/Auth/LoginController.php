<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Providers\RouteServiceProvider;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

            // Cognito上の認証情報を取得
            $result = Auth::guard("web")->loginByDb($credentials);
            // セッションID
            $sessionId = $request->cookie("cognito-session-id");

            if (!$result) {
                // ログインできなかった場合
                throw ValidationException::withMessages([
                    $this->username() => "Login Failed.",
                ]);
            }

            /** @var $user array Cognitoの情報 */
            $cognito = $result["cognito"];
            $auth = $cognito["auth"]["AuthenticationResult"];

            // Cookieを更新
            $session = Session::firstOrNew([
                    "uuid" => $sessionId ?: Str::uuid(),
                ])
                ->fill([
                    // ユーザー名
                    "cognito_username" => $cognito["user"]["Username"],
                    // IDトークン（主に認可用に引き回すトークン）
                    "id_token" => $auth["IdToken"],
                    // アクセストークン（emailやpasswordの属性更新などのセキュアな処理を呼ぶときに必要になるトークン）
                    "access_token" => $auth["AccessToken"],
                    // リフレッシュトークン
                    "refresh_token" => $auth["RefreshToken"],
                    // IPアドレス
                    "ip_address" => $request->ip(),
                    // ユーザーエージェント
                    "user_agent" => $request->userAgent(),
                ])
                ->save();

            $cookie = cookie()->forever("cognito-session-id", $session->uuid);
            cookie()->queue($cookie);

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
        $cookieName = "cognito-session-id";

        /** セッションID */
        $sessionId = $request->cookie($cookieName);

        // セッションログイン情報をリセットする
        $session = Session::find($sessionId);
        if ($session->exists) {
            $session
                ->fill([
                    "id_token" => null,
                    "access_token" => null,
                    "refresh_token" => null,
                ])
                ->save();
        }

        return redirect('/');
    }

}
