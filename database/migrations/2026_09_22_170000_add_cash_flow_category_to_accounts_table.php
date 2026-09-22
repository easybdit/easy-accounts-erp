<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('cash_flow_category', ['operating', 'investing', 'financing'])
                ->default('operating')->after('is_bank_account');
        });

        // Sensible default backfill for existing accounts: equity is
        // financing activity; everything else (income, expense, and
        // asset/liability accounts, which are overwhelmingly AR/AP-like in
        // a small business chart of accounts) defaults to operating. A
        // fixed asset or loan account can be reclassified to
        // investing/financing individually from the Chart of Accounts —
        // this column is a per-account override, not a guess re-made on
        // every report run.
        DB::table('accounts')->where('type', 'equity')->update(['cash_flow_category' => 'financing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('cash_flow_category');
        });
    }
};
