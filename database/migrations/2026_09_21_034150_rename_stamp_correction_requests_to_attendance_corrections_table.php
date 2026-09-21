<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('stamp_correction_requests', 'attendance_corrections');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('attendance_corrections', 'stamp_correction_requests');
    }
};
