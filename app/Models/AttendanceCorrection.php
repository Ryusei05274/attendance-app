<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'original_date',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * リレーション：所属するユーザー (親)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション：所属する修正対象の勤怠データ (親)
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}