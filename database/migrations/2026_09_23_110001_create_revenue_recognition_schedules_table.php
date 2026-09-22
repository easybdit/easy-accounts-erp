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
        Schema::create('revenue_recognition_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->cascadeOnDelete();

            // Explicit short constraint names: the auto-generated name
            // ("revenue_recognition_schedules_deferred_revenue_account_id_foreign")
            // exceeds MySQL's 64-character identifier limit.
            $table->foreignId('deferred_revenue_account_id')->constrained('accounts', indexName: 'rrs_deferred_revenue_account_fk')->restrictOnDelete();
            $table->foreignId('income_account_id')->constrained('accounts', indexName: 'rrs_income_account_fk')->restrictOnDelete();
            $table->decimal('total_amount', 19, 4);
            $table->unsignedInteger('months_total');
            $table->unsignedInteger('months_recognized')->default(0);
            $table->date('next_period_date')->nullable();
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_recognition_schedules');
    }
};
