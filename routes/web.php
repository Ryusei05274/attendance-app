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
    
    // 2. 打刻ボタンが押された時のステータス更新処理（POST通信）
    Route::post('/attendance', [AttendanceController::class, 'updateStatus'])->name('attendance.update');
    
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'showDetail'])->name('attendance.detail');

    // 申請一覧画面の表示
Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
    ->name('stamp_correction_request.index');
    
});