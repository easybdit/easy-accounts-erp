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
        Schema::create('online_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_payment_link_id')
                ->constrained('invoice_payment_links', indexName: 'opt_payment_link_fk')->restrictOnDelete();
            $table->string('tran_id')->unique();
            $table->decimal('amount', 19, 4);
            $table->string('currency', 10);
            $table->enum('status', ['initiated', 'validated', 'failed', 'cancelled'])->default('initiated');
            $table->string('val_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_payment_transactions');
    }
};
