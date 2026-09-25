<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('attendance.table_names.employees', 'easyattendance_employees'), function (Blueprint $table) {
            $table->enum('grade', ['A', 'B', 'C'])->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table(config('attendance.table_names.employees', 'easyattendance_employees'), function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
