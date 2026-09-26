<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\AttendanceCorrectionController as AdminAttendanceCorrectionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページにアクセスした時の処理
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('attendance.register');
    }
    return redirect('/login');
});

// ─────────────────────────────────────────────────────────────────
// 👤 【未ログイン限定】一般ユーザー ＆ 管理者 認証ルート
// ─────────────────────────────────────────────────────────────────
Route::middleware(['guest'])->group(function () {
    // 会員登録画面（一般ユーザー）
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // ログイン画面（一般ユーザー）
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // ログイン画面（管理者）
    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store']);
});

// ─────────────────────────────────────────────────────────────────
// 🔒 ログイン認証（auth）が必要なルートグループ
// ─────────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    
    // 共通ログアウト処理
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');
    
    // ─────────────────────────────────────────────────────────────
    // 👤 一般ユーザー側の画面機能 (黄色枠セクション)
    // ─────────────────────────────────────────────────────────────
    // 出勤登録画面
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.register');
    Route::post('/attendance', [AttendanceController::class, 'updateStatus'])->name('attendance.update');
    
    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');
    
    // 💡【一般・管理者 共通URL】勤怠詳細画面 (GET: 表示のみ)
    Route::get('/attendance/{attendance_id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    
    // 💡【一般・管理者 共通URL】勤怠詳細からの修正送信 (PUT: 直接上書き または 申請用)
    Route::put('/attendance/{attendance_id}', [AttendanceController::class, 'update'])->name('attendance.detail.update');

    // 💡 【一般・管理者 共通URL】申請一覧画面のルート
    Route::get('/attendance_correction_request/list', [AttendanceCorrectionController::class, 'index'])->name('attendance_correction.list');
    
    // 💡 【リダイレクト裏口】古いURLをすべて上記の新しい共通URLへ自動転送
    Route::get('/attendance/correction-request/list', function() {
        return redirect()->route('attendance_correction.list');
    });
    Route::get('/admin/attendance/correction-request/list', function() {
        return redirect()->route('attendance_correction.list');
    });
    Route::get('/stamp_correction_request/list', function() {
        return redirect()->route('attendance_correction.list');
    });
    
    // ─────────────────────────────────────────────────────────────
    // 👑 管理者側の画面機能 (緑色枠セクション：要件定義PG10〜PG13対応)
    // ─────────────────────────────────────────────────────────────
    // PG04: 勤怠一覧画面（管理者）➔ 上部のuse定義（AdminAttendanceController）を正しく使って結線
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');

    // 一覧画面の「詳細」リンク（/admin/{id}）がクリックされた際、一本化した共通URL（/attendance/{id}）へ自動転送
    Route::get('/admin/{attendance_id}', function($attendance_id) {
        return redirect()->route('attendance.detail', ['attendance_id' => $attendance_id]);
    })->name('admin.admin-attendance.detail');

    // 詳細画面の<form>から送信されたPOST通信を、共通のPUT処理へ仲介
    Route::post('/attendance/{attendance_id}', [AttendanceController::class, 'update']);

    // 💡 詳細の修正フォームが探す古いルート名（admin.attendance.update）の裏口を正しく定義
    Route::put('/admin/{attendance_id}', [AttendanceController::class, 'update'])->name('admin.attendance.update');
    
    // PG10: スタッフ一覧画面（管理者）
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.list');
    
    // PG11: スタッフ別勤怠一覧画面（管理者）
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff'])->name('admin.attendance.staff');

    // PG13: 修正申請承認画面（管理者）
    Route::get('/attendance_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceCorrectionController::class, 'showApprove'])->name('admin.correction.approve');
    Route::post('/attendance_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceCorrectionController::class, 'approve']);

    // 💡 承認画面の古いURL（/admin/...）でアクセスが来た場合の裏口リダイレクト
    Route::get('/admin/attendance/correction-request/approve/{id}', function($id) {
        return redirect()->route('admin.correction.approve', ['attendance_correct_request_id' => $id]);
    });
    Route::post('/admin/attendance/correction-request/approve/{id}', [AdminAttendanceCorrectionController::class, 'approve']);

});