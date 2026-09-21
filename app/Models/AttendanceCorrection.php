<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可するカラム（設計書に基づく項目一覧）
     */
    protected $fillable = [
        'user_id',
        'attendance_id',
        'status',
        'original_start_time',
        'original_end_time',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'approved_at',
    ];

    /**
     * データを取得する際の日時キャスト設定（必要に応じて）
     */
    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * 紐づくユーザー（User）とのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 紐づく勤怠データ（Attendance）とのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}