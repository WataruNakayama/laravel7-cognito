<?php

namespace App\Auth;

use App\Models\Session;
use App\Services\Cognito\CognitoClient;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\HttpFoundation\Request;

class CognitoGuard implements Guard
{
    use GuardHelpers;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $doneLogin = false;

    /**
     * @var string
     */
    protected $typeName;

    /**
     * @var CognitoClient
     */
    protected CognitoClient $client;

    /**
     * ProffitMartGuard constructor.
     * @param UserProvider $provider
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(UserProvider $provider, Request $request, $name)
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->typeName = $name;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if (!$this->doneLogin) {
            $this->user = $this->login();
        }

        return $this->user;
    }

    /**
     * ログイン処理（cookieの値からログインユーザーを復元する）
     * @return Authenticatable|null
     * @throws BindingResolutionException
     */
    public function login()
    {
        $this->doneLogin = true;

        /** cookieから取得したセッションID */
        $sessionId = request()->cookie("cognito-session-id");

        $session = Session::find($sessionId);
        $accessToken = $session->access_token ?? "";

        if ($accessToken) {
            // アクセストークンがある場合、毎回Cognitoに問い合わせて検証する
            app()
                ->make(CognitoClient::class)
                ->getUser($accessToken);
        }

        // Cognito上のユーザー名で取得する
        $user = $this->provider->retrieveByUsername($accessToken ? $session->cognito_username : "");
        $this->user = $user;

        return $user;
    }

    /**
     * ID/passwordを指定したログインの実行
     * @param array $credentials
     * @return Authenticatable|null
     */
    public function loginByDb(array $credentials)
    {
        $this->doneLogin = true;

        $result = $this->provider->retrieveByCredentials($credentials);

        if ($result && $result["model"]) {
            $this->setUser($result["model"]);
            return $result;
        }

        return null;
    }

    /**
     * ID/passwordを指定したログインの実行
     * @param array $credentials
     * @return Authenticatable|null
     */
    public function loginByEmail(array $credentials)
    {
        $this->doneLogin = true;

        // メールアドレスでDBから単体Userを取得して返す
        $result = $this->provider->retrieveByCredentials($credentials);

        if ($result) {
            $this->setUser($result["model"]);
            return $result;
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return true;
    }
}
