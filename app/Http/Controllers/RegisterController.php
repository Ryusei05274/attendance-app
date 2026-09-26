<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * PG01: 会員登録画面の表示
     */
    public function create()
    {
        // 💡こちらも実際の配置に合わせてView名を正しく指定します
        // 例: 'user.register' または 'register'
        return view('user.register'); 
    }

    /**
     * FN001〜FN005: 会員登録の実行 ＆ 指定文言エラー
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
        ], [
            'name.required'     => 'お名前を入力してください',
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => 'メールアドレスは「ユーザー名@ドメイン」形式で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min'      => 'パスワードは8文字以上で入力してください',
            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.same'     => 'パスワードと一致しません',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user', // 初期値は一般ユーザー
        ]);

        // 会員登録直後に自動でログインさせ、打刻画面へ遷移 (タスク6要件)
        Auth::login($user);

        return redirect()->route('attendance.register');
    }
}