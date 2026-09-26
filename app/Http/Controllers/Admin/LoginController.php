<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * PG07: 管理者ログイン画面の表示
     */
    public function create()
    {
        return view('admin.admin-login');
    }

    /**
     * FN014〜FN016: 管理者ログインの実行（正規の認証ロジック）
     */
    public function store(Request $request)
    {
        // 1. バリデーション
        $credentials = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => 'メールアドレスは「ユーザー名@ドメイン」形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ]);

        // 2. 認証処理（管理者権限 role => admin の条件も追加）
        $credentials['role'] = 'admin';

        // 💡 【重要】Auth::guard('admin')->attempt に修正し、管理者の鍵で厳格にログインさせます
        if (Auth::attempt($credentials)) { 
            // 認証成功時、セッションを再生成してリダイレクト
            $request->session()->regenerate();
            return redirect('/admin/attendance/list'); 
        }

        // 3. 認証失敗時のエラー返却
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput($request->only('email'));
    }

    /**
     * FN017: 管理者ログアウト処理
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}