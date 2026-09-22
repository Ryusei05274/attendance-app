<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];

    /**
     * リレーション：所属するユーザー (親)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション：紐づく複数の休憩ログ (子)
     */
    public function breakLogs()
    {
        return $this->hasMany(BreakLog::class);
    }

    /**
     * リレーション：紐づく複数の修正申請 (子)
     */
    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}