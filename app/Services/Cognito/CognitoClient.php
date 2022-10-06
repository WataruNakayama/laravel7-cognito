<?php


namespace App\Services\Cognito;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\Result;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

/**
 * Cognitoの制御を行うクラス
 * @package App\Services\Cognito
 */
class CognitoClient
{
    public const NEW_PASSWORD_CHALLENGE = 'NEW_PASSWORD_REQUIRED';
    public const FORCE_PASSWORD_STATUS  = 'FORCE_CHANGE_PASSWORD';
    public const RESET_REQUIRED         = 'PasswordResetRequiredException';
    public const USER_NOT_FOUND         = 'UserNotFoundException';
    public const USERNAME_EXISTS        = 'UsernameExistsException';
    public const INVALID_PASSWORD       = 'InvalidPasswordException';
    public const CODE_MISMATCH          = 'CodeMismatchException';
    public const EXPIRED_CODE           = 'ExpiredCodeException';

    /**
     * Cognitoクライアント
     * @var CognitoIdentityProviderClient
     */
    public CognitoIdentityProviderClient $client;

    /**
     * クライアントID
     */
    private string $clientId;

    /**
     * クライアントシークレット
     */
    private string $clientSecret;

    /**
     * ユーザープールID
     */
    private string $userPoolId;

    /**
     * CognitoClient constructor.
     */
    public function __construct()
    {
        /** Cognitoの設定値 */
        $config = [
            'region' => config("core_services.cognito.region"),
            'version' => config("core_services.cognito.version"),
        ];

        // 設定があればcredentialsを読み込む（主にローカル動作用の設定）
        $key = config("core_services.cognito.key");
        $secret = config("core_services.cognito.secret");
        if ($key && $secret) {
            $config["credentials"] = [
                "key" => $key,
                "secret" => $secret,
            ];
        }

        $this->client = new CognitoIdentityProviderClient($config);
        $this->clientId = config("core_services.cognito.client_id");
        $this->clientSecret = config("core_services.cognito.client_secret");
        $this->userPoolId = config("core_services.cognito.user_pool_id");
    }

    /**
     * ユーザー名に応じた認証用のハッシュ値を返す
     * @param string $username ユーザー名（主にメールアドレス）
     * @return string
     */
    protected function cognitoSecretHash(string $username): string
    {
        $hash = hash_hmac('sha256', $username . $this->clientId, $this->clientSecret, true);
        return base64_encode($hash);
    }

    /**
     * ユーザーを新規作成する
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return Result
     */
    public function adminCreateUser(string $email, string $password): Result
    {
        return $this->client->adminCreateUser([
            'UserPoolId' => $this->userPoolId,
            'ClientId' => $this->clientId,
            'Username' => $email,
            'TemporaryPassword' => $password,
            'UserAttributes' => [
                [
                    'Name' => 'email',
                    'Value' => $email,
                ],
                [
                    'Name' => 'email_verified',
                    'Value' => 'false',
                ],
            ],
            // Cognito側から確認メールを送信させない
            "DesiredDeliveryMediums" => [],
            "MessageAction" => "SUPPRESS"
        ]);
    }

    /**
     * 指定ユーザーのメールアドレス確認状況を「確認済み」に更新する
     * @param string $email メールアドレス
     * @return Result
     */
    public function forceVerifyUserEmail(string $email): Result
    {
        return $this->adminUpdateUserAttributes($email, [
            [
                'Name' => 'email_verified',
                'Value' => 'true',
            ]
        ]);
    }

    /**
     * 指定ユーザーのメールアドレスを変更する
     * @param string $previousEmail 旧メールアドレス
     * @param string $proposedEmail 新メールアドレス
     * @return Result
     */
    public function changeUserEmail(string $previousEmail, string $proposedEmail): Result
    {
        return $this->adminUpdateUserAttributes($previousEmail, [
            [
                'Name' => 'email',
                'Value' => $proposedEmail,
            ],
            [
                // メールアドレス変更時は強制的に認証済みにする
                'Name' => 'email_verified',
                'Value' => 'true',
            ],
        ]);
    }

    /**
     * パスワードを変更する
     * （主にユーザー自身による変更時に実行する）
     * @param string $accessToken アクセストークン
     * @param string $previousPassword 旧パスワード
     * @param string $proposedPassword 新パスワード
     * @return Result
     */
    public function changePassword(string $accessToken, string $previousPassword, string $proposedPassword): Result
    {
        return $this->client->changePassword([
            "AccessToken" => $accessToken,
            "PreviousPassword" => $previousPassword,
            "ProposedPassword" => $proposedPassword,
        ]);
    }

    /**
     * 指定ユーザーの要素を更新する
     * @param string $email メールアドレス
     * @param array $userAttributes 更新項目
     * @return Result
     */
    public function adminUpdateUserAttributes(string $email, array $userAttributes): Result
    {
        return $this->client->adminUpdateUserAttributes([
            'UserPoolId' => $this->userPoolId,
            'Username' => $email,
            'UserAttributes' => $userAttributes,
        ]);
    }

    /**
     * 指定ユーザーのパスワード設定状態をCONFIRMEDに更新する
     * （これを実行しないと、作成されたユーザーのAccount StatusがUNCOFIRMEDのままになってしまう）
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return Result
     */
    public function adminSetUserPassword(string $email, string $password): Result
    {
        return $this->client->adminSetUserPassword([
            'UserPoolId' => $this->userPoolId,
            'Username' => $email,
            'Password' => $password,
            'Permanent' => true,
        ]);
    }

    /**
     * 指定ユーザーを有効化する
     * @param string $email メールアドレス
     * @return Result
     */
    public function adminEnableUser(string $email): Result
    {
        return $this->client->adminEnableUser([
            'UserPoolId' => $this->userPoolId,
            'Username' => $email,
        ]);
    }

    /**
     * 指定ユーザーを無効化する
     * @param string $email メールアドレス
     * @return Result
     */
    public function adminDisableUser(string $email): Result
    {
        return $this->client->adminDisableUser([
            'UserPoolId' => $this->userPoolId,
            'Username' => $email,
        ]);
    }

    /**
     * 指定ユーザーを削除する
     * @param string $email メールアドレス
     * @return Result
     */
    public function adminDeleteUser(string $email): Result
    {
        return $this->client->adminDeleteUser([
            'UserPoolId' => $this->userPoolId,
            'Username' => $email,
        ]);
    }

    /**
     * 指定ユーザーを取得する
     * @param string $email メールアドレス
     * @return Result
     */
    public function adminGetUser(string $email): Result
    {
        return $this->client->adminGetUser([
            'UserPoolId' => $this->userPoolId,
            'Username' => $email,
        ]);
    }

    /**
     * 指定ユーザーでログイン処理を実行
     * （主にアクセストークン情報などを取得するために実行する）
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return Result
     */
    public function adminInitiateAuth(string $email, string $password): Result
    {
        return $this->client->adminInitiateAuth([
            'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
            'ClientId' => $this->clientId,
            'UserPoolId' => $this->userPoolId,
            'AuthParameters' => [
                'USERNAME' => $email,
                'PASSWORD' => $password,
                'SECRET_HASH' => $this->cognitoSecretHash($email),
            ],
        ]);
    }
}
