<?php

namespace App\Auth;

use App\Services\Cognito\CognitoClient;
use Aws\Result;
use Exception;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Request;

class CognitoGuard extends SessionGuard
{
    /**
     * @var CognitoClient
     */
    protected CognitoClient $client;

    /**
     * CognitoGuard constructor.
     * @param string $name
     * @param CognitoClient $client
     * @param UserProvider $provider
     * @param Session $session
     * @param null|Request $request

     */
    public function __construct(
        string $name,
        CognitoClient $client,
        UserProvider $provider,
        Session $session,
        ?Request $request = null
    ) {
        $this->client = $client;
        parent::__construct($name, $provider, $session, $request);
    }

    /**
     * @param mixed $user
     * @param array $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials): bool
    {
        $isAuthenticated = false;

        try {
            // 指定されたメールアドレスとパスワードで認証処理を実行
            $isAuthenticated = (bool)$this
                ->client
                ->adminInitiateAuth($credentials['email'], $credentials['password']);
        } catch (Exception $e) {
            // 認証エラーは握り潰す
            $e;
        }

        return $isAuthenticated;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @throws
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false): bool
    {
        $this->fireAttemptEvent($credentials, $remember);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }

        $this->fireFailedEvent($user, $credentials);

        return false;
    }
}
