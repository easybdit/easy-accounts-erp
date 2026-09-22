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
            // Denormalized copy of the parent journal's date (Section 17: Ledger
            // entries carry a Date). Avoids joining to journals for every
            // General Ledger / Trial Balance query (Section 54 performance).
            $table->date('date')->nullable()->after('account_id');
            $table->index(['account_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'date']);
            $table->dropColumn('date');
        });
    }
};
