<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            // No receivable_account_id (Section 12/28): a Sales Receipt is
            // settled in full at the point of sale, so it debits a deposit
            // account (Cash/Bank/Undeposited Funds) directly, the same
            // field Payment.deposit_account_id already uses — never posts
            // to Accounts Receivable.
            $table->foreignId('deposit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('receipt_date');
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_receipts');
    }
};
