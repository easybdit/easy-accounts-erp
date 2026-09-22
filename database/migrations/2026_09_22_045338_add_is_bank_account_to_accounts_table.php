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
        Schema::table('accounts', function (Blueprint $table) {
            // Distinguishes actual Cash/Bank accounts from other asset-type
            // accounts (Accounts Receivable, Inventory, ...) for the
            // Banking overview (Section 31). Only meaningful when
            // type = 'asset'; enforced in StoreAccountRequest.
            $table->boolean('is_bank_account')->default(false)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('is_bank_account');
        });
    }
};
