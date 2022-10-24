<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\ChangePasswordRequest;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Cognito\CognitoClient;
use Illuminate\Support\Facades\DB;

class ChangeEmailController extends Controller
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
     * メールアドレス更新フォーム表示
     *
     * @param Request $request
     */
    public function show(Request $request)
    {
        return view('auth.change_email');
    }

    /**
     * メールアドレス更新
     *
     * @param ChangePasswordRequest $request
     */
    public function update(ChangePasswordRequest $request)
    {
        DB::transaction(function () use ($request) {
            $newEmail = $request->get("email");

            // DB上のメールアドレスを更新
            /** @var $user User */
            $user = auth()->user();
            $oldEmail = $user->email;
            $user->email = $newEmail;
            $user->save();

            // Cognito上のメールアドレスを更新
            app()->make(CognitoClient::class)->changeUserEmail($oldEmail, $newEmail);
        });

        return redirect('/home')->with('status', 'メールアドレスを変更しました');
    }
}
