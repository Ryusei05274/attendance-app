<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    // 💡 仕様書の画像に書かれている本物のテーブル名を指定します
    protected $table = 'attendance_corrections';

    // 安全に一括保存（更新）できるようにカラム名を登録します
    protected $fillable = [
        'user_id',
        'attendance_id',
        'status',
        'ororiginal_date',
        'new_clock_in',
        'new_clock_out',
        'new_break_in',
        'new_break_out',
        'comment',
        'approved_at',
    ];

    // 🔗 誰からの申請か（Userモデルとのリレーション）
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🔗 どの打刻データに対する申請か（Attendanceモデルとのリレーション）
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}