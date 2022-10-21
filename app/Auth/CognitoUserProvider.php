<?php

namespace App\Auth;

use App\Services\Cognito\CognitoClient;
use App\User;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * proffit-mart用のユーザ認識・情報取得クラス
 * Class ProffitMartUserProvider
 * @package App\Auth
 */
class CognitoUserProvider extends EloquentUserProvider
{
    /**
     * 主キー（ID）でDB上のユーザーを取得する
     * @param $identifier
     * @return Authenticatable|Builder|Model|object|null
     */
    public function retrieveById($identifier)
    {
        /** @var $model User */
        $model = $this->createModel();

        return $model
            ->newQuery()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    /**
     * Cognito上のユーザー名でDB上のユーザーを取得する
     * @param $identifier
     * @return Builder|Model|object|null
     */
    public function retrieveByUsername($identifier)
    {
        /** @var $model User */
        $model = $this->createModel();

        return $model
            ->newQuery()
            ->where("cognito_username", $identifier)
            ->first();
    }

    /**
     * メールアドレス + パスワードで認証を行った上でユーザー情報を返す
     * @param array $credentials
     * @return array|null
     */
    public function retrieveByCredentials(array $credentials): ?array
    {
        $model = $this->createModel();

        $client =  app()->make(CognitoClient::class);

        // Cognitoの認証とユーザー取得を行う
        $cognitoResult =  [
            // 指定されたメールアドレスとパスワードで認証処理を実行
            "auth" => $client->adminInitiateAuth($credentials['email'], $credentials['password']),
            // Cognito上のユーザー情報を取得
            "user" => $client->adminGetUser($credentials['email']),
        ];

        return [
            "cognito" => $cognitoResult,
            // Cognitoのユーザ名に紐づいたモデルを取得
            "model" => $model
                ->newQuery()
                ->where("cognito_username", $cognitoResult["user"]["Username"])
                ->first(),
        ];
    }

    /**
     * メールアドレス単体による認証を行う（主にパスワードリセットなどのログインを伴わない場合に利用）
     * @param array $credentials
     * @return array|null
     */
    public function retrieveByEmail(array $credentials): ?array
    {
        $model = $this->createModel();

        $client =  app()->make(CognitoClient::class);

        // Cognitoの認証とユーザー取得を行う
        $cognitoResult =  [
            // 指定されたパスワード設定前のなので認証を行わない
            "auth" => null,
            // Cognito上のユーザー情報を取得
            "user" => $client->adminGetUser($credentials['email']),
        ];

        return [
            "cognito" => $cognitoResult,
            // Cognitoのユーザ名に紐づいたモデルを取得
            "model" => $model
                ->newQuery()
                ->where("cognito_username", $cognitoResult["user"]["Username"])
                ->first(),
        ];
    }
}
