<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakLog extends Model
{
    use HasFactory;

    // 💡 以下の項目の一括保存を許可します
    protected $fillable = [
        'attendance_id',
        'break_in',
        'break_out',
    ];
}