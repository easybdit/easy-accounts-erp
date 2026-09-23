<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_receipt_id')->constrained('sales_receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('description');
            $table->decimal('quantity', 19, 4)->default(1);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->restrictOnDelete();
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->foreignId('tax_rate_2_id')->nullable()->constrained('tax_rates')->restrictOnDelete();
            $table->decimal('tax_amount_2', 19, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_receipt_items');
    }
};
