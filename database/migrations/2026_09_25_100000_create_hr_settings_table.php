<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singleton settings row (always id=1), mirroring accounting_settings —
     * lets an administrator change HR policy from the UI instead of
     * editing .env/config on the server (see AppServiceProvider::
     * applySettingsOverrides()).
     */
    public function up(): void
    {
        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->json('special_working_day_grade_rates')->nullable();
            $table->unsignedInteger('late_deduction_ratio')->nullable();
            $table->unsignedInteger('late_warning_threshold')->default(3);
            $table->boolean('multi_step_leave_approval_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_settings');
    }
};
