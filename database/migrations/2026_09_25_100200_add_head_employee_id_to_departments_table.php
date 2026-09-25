<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('attendance.table_names.departments', 'easyattendance_departments'), function (Blueprint $table) {
            $table->foreignId('head_employee_id')
                ->nullable()
                ->after('description')
                ->constrained(config('attendance.table_names.employees', 'easyattendance_employees'))
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(config('attendance.table_names.departments', 'easyattendance_departments'), function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_employee_id');
        });
    }
};
