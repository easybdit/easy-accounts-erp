<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a login account to an HR employee record (from
     * easybdit/laraveleasyattendance) for self-service leave/payroll views.
     * Nullable: not every user is an employee, and not every employee has a
     * login.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('id')
                ->constrained(config('attendance.table_names.employees', 'easyattendance_employees'))
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
