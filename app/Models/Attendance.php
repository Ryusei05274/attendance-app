<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // 💡 以下の項目の一括保存（登録）を許可します
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];
}