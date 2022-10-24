<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Cognito\CognitoClient;
use Illuminate\Support\Facades\DB;

class ChangePasswordController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * パスワード更新フォーム表示
     *
     * @param Request $request
     */
    public function show(Request $request)
    {
        return view('auth.change_password');
    }

    /**
     * パスワード更新
     *
     * @param ChangePasswordRequest $request
     */
    public function update(ChangePasswordRequest $request)
    {
        DB::transaction(function () use ($request) {
            $password = $request->get("password");
            $client = app()->make(CognitoClient::class);

            // 強制的にパスワード変更済みにする
            $client->adminSetUserPassword(auth()->user()->email, $password);
        });

        return redirect('/home')->with('status', 'パスワードを変更しました');
    }
}
