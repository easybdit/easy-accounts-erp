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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->boolean('is_deferred')->default(false)->after('tax_amount');
            $table->unsignedInteger('deferred_months')->nullable()->after('is_deferred');
            $table->foreignId('deferred_revenue_account_id')->nullable()->after('deferred_months')
                ->constrained('accounts')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deferred_revenue_account_id');
            $table->dropColumn(['is_deferred', 'deferred_months']);
        });
    }
};
