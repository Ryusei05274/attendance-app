<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * PG02: 一般ログイン画面の表示
     */
    public function create()
    {
        return view('user.user-login'); // プロジェクトのViewファイル名に合わせてください
    }

    /**
     * FN006〜FN010: 一般ログインの実行 ＆ 指定文言エラー整備
     */
    public function store(Request $request)
    {
        // 評価項目である指定文言のエラーメッセージを完全再現します
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => 'メールアドレスは「ユーザー名@ドメイン」形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ]);

        // ログイン試行
        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            // 管理者が一般ログインから入ってきた場合は管理者用トップへリダイレクト
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.attendance.list');
            }

            // 一般ユーザーは打刻画面へ
            return redirect()->route('attendance.register');
        }

        // ログイン失敗時の指定エラー文言 (FN010)
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput($request->only('email', 'remember'));
    }

    /**
     * FN013: ログアウト処理
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}