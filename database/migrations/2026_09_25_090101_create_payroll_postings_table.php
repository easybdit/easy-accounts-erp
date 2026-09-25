<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a posted salary slip to the journal it produced, and blocks
     * double-posting the same slip. Carries a snapshot of the employee/
     * period so posted history stays readable even if the attendance
     * package is later swapped or its records purged.
     */
    public function up(): void
    {
        Schema::create('payroll_postings', function (Blueprint $table) {
            $table->id();
            $table->string('salary_slip_type');
            $table->unsignedBigInteger('salary_slip_id');
            $table->foreignId('journal_id')->constrained('journals')->restrictOnDelete();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->decimal('gross_amount', 14, 4);
            $table->decimal('total_deductions', 14, 4);
            $table->decimal('net_amount', 14, 4);
            $table->string('employee_name');
            $table->string('employee_code')->nullable();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->unique(['salary_slip_type', 'salary_slip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_postings');
    }
};
