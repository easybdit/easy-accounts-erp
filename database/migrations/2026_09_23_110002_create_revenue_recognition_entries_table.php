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
        Schema::create('revenue_recognition_entries', function (Blueprint $table) {
            $table->id();
            // Explicit short constraint name: the auto-generated name
            // exceeds MySQL's 64-character identifier limit.
            $table->foreignId('revenue_recognition_schedule_id')
                ->constrained('revenue_recognition_schedules', indexName: 'rre_schedule_fk')->cascadeOnDelete();
            $table->date('period_date');
            $table->decimal('amount', 19, 4);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['revenue_recognition_schedule_id', 'period_date'], 'rre_schedule_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_recognition_entries');
    }
};
