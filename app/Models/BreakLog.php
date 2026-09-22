<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_in',
        'break_out',
    ];

    /**
     * リレーション：所属する勤怠データ (親)
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}