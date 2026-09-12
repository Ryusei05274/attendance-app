<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance; // 必要に応じて修正・申請のモデルを読み込んでください

class StampCorrectionRequestController extends Controller
{
    /**
     * 💡 申請一覧画面の表示処理
     */
    public function index()
    {
        $user = Auth::user();

        // ここでユーザー自身の申請履歴などのデータを取得します（仮のコードです）
        // $requests = StampCorrectionRequest::where('user_id', $user->id)->get();

        return view('user.stamp-correction-request-list', [
            'user' => $user,
        ]);
    }
}