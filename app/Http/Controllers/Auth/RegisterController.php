<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Cognito\CognitoClient;
use App\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
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
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     * @throws BindingResolutionException
     */
    protected function create(array $data): User
    {
        $client = app()->make(CognitoClient::class);
        $email = $data['email'];
        $password = $data['password'];

        // ユーザーを作成
        $result = $client->adminCreateUser($email, $password);

        // メールアドレスを確認済みにする（Proffitではパスワードリセットのタイミングで実行する）
        $client->forceVerifyUserEmail($email);

        // 強制的にパスワード変更済みにする（Proffitではパスワードリセットのタイミングで実行する）
        $client->adminSetUserPassword($email, $password);

        return User::create([
            'name' => $data['name'],
            'cognito_username' => $result->get("User")["Username"],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
