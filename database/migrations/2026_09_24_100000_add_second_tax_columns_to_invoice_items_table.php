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
            $table->foreignId('tax_rate_2_id')->nullable()->after('tax_rate_id')
                ->constrained('tax_rates')->restrictOnDelete();
            $table->decimal('tax_amount_2', 19, 4)->default(0)->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_rate_2_id');
            $table->dropColumn('tax_amount_2');
        });
    }
};
