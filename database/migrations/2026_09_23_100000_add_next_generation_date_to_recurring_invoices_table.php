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
        Schema::table('recurring_invoices', function (Blueprint $table) {
            // Null means manual-only ("Generate Now" button, unchanged
            // behavior); set means the scheduled command auto-generates a
            // new draft on/after this date, then advances it by one month.
            $table->date('next_generation_date')->nullable()->after('is_active');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('recurring_invoice_id')->nullable()->after('customer_id')
                ->constrained('recurring_invoices')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_invoice_id');
        });

        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn('next_generation_date');
        });
    }
};
