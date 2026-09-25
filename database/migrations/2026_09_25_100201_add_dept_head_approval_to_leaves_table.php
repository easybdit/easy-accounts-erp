<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A leave request's optional first approval stage (its department
     * head), tracked separately from the package's own `status` column
     * (which is the final HR decision). 'skipped' means either multi-step
     * approval is off, or the employee's department has no head set — the
     * request behaves exactly as it did before this feature existed.
     */
    public function up(): void
    {
        Schema::table(config('attendance.table_names.leaves', 'easyattendance_leaves'), function (Blueprint $table) {
            $table->enum('dept_head_status', ['pending', 'approved', 'rejected', 'skipped'])
                ->default('skipped')
                ->after('status');
            $table->foreignId('dept_head_by')->nullable()->after('dept_head_status')->constrained('users')->nullOnDelete();
            $table->timestamp('dept_head_at')->nullable()->after('dept_head_by');
            $table->text('dept_head_note')->nullable()->after('dept_head_at');
        });
    }

    public function down(): void
    {
        Schema::table(config('attendance.table_names.leaves', 'easyattendance_leaves'), function (Blueprint $table) {
            $table->dropConstrainedForeignId('dept_head_by');
            $table->dropColumn(['dept_head_status', 'dept_head_at', 'dept_head_note']);
        });
    }
};
