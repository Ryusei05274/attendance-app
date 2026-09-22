<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController; 
use App\Http\Controllers\AttendanceCorrectionController;

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

// ログイン認証（auth）が必要なルートグループ
Route::middleware(['auth'])->group(function () {
    
    // 1. 勤怠画面の表示（GET通信）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.register');
    
    // 2. 打刻ボタンのステータス更新処理（POST通信）
    Route::post('/attendance', [AttendanceController::class, 'updateStatus'])->name('attendance.update');
    
    // 3. 勤怠一覧画面（一般ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');
    
    // 4. 勤怠詳細画面（一般ユーザー） 
    Route::get('/attendance/detail/{attendance_id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    
    // 5. 修正申請ボタン押下時の保存処理（POST通信） 
    Route::post('/attendance/detail/{attendance_id}', [AttendanceController::class, 'updateDetailRequest'])->name('attendance.detail.update');

    // 6. 申請一覧画面（一般ユーザー） 
    Route::get('/attendance/correction-request/list', [AttendanceCorrectionController::class, 'index'])
        ->name('attendance_corrections.index');
    
});