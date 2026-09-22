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
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->timestamp('reconciled_at')->nullable()->after('credit');
            $table->foreignId('bank_reconciliation_id')->nullable()->after('reconciled_at')
                ->constrained('bank_reconciliations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_reconciliation_id');
            $table->dropColumn('reconciled_at');
        });
    }
};
