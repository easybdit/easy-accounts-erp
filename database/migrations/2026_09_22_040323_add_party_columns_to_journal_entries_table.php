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
            // Optional subsidiary-ledger tagging (Section 26/27: Customer/Vendor
            // Transaction History) — a line may optionally belong to a customer
            // OR a vendor, never both (enforced in StoreJournalRequest).
            $table->foreignId('customer_id')->nullable()->after('account_id')
                ->constrained('customers')->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->after('customer_id')
                ->constrained('vendors')->restrictOnDelete();

            $table->index(['customer_id', 'date']);
            $table->index(['vendor_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
