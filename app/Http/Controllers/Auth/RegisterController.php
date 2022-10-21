<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Cognito\CognitoClient;
use App\User;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;

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
     * @return Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]);
    }

    /**
     * 仮登録処理
     *
     * @param  Request  $request
     */
    public function register(Request $request)
    {
        DB::transaction(function () use ($request) {
            // バリデーション
            $this->validator($request->all())->validate();

            $params = $request->all();

            $client = app()->make(CognitoClient::class);
            $email = $params['email'];

            // Cognito上でユーザーを作成
            $result = $client->adminCreateUser($email);

            // DB上に仮登録状態のユーザーを作成
            User::create([
                'name' => $params['name'],
                'cognito_username' => $result->get("User")["Username"],
                'email' => $email,
            ]);

            // パスワード更新メールを送信
            Password::sendResetLink(['email' => $email]);
        });

        return view('auth.register_complete');
    }

    /**
     * 本登録ページ（パスワード設定）の表示
     * @param Request $request
     * @return Application|Factory|View
     */
    public function showPasswordForm(Request $request)
    {
        return view('auth.passwords.register', [
            "token" => $request->get("token"),
            "email" => $request->get("email"),
        ]);
    }

    /**
     * 本登録の実行
     * @param Request $request
     */
    public function updatePassword(Request $request)
    {
        DB::transaction(function () use ($request) {
            $email = $request->get("email");
            $password = $request->get("password");

            // DBからユーザー情報を取得
            User::query()
                ->where("email", $email)
                ->firstOrFail()
                ->fill([
                    // ステータスを本登録に更新
                    "status" => 2,
                    "email_verified_at" => Carbon::now(),
                ])
                ->save();

            $client = app()->make(CognitoClient::class);

            // メールアドレスを確認済みにする
            $client->forceVerifyUserEmail($email);

            // 強制的にパスワード変更済みにする
            $client->adminSetUserPassword($email, $password);
        });

        // ログインページに戻す
        return redirect('/login');
    }

    public function complete()
    {
        return view('auth.register_complete');
    }
}
