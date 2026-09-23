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
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->foreignId('withholding_tax_rate_id')->nullable()->after('amount')
                ->constrained('withholding_tax_rates')->nullOnDelete();
            $table->decimal('withholding_tax_amount', 19, 4)->default(0)->after('withholding_tax_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withholding_tax_rate_id');
            $table->dropColumn('withholding_tax_amount');
        });
    }
};
