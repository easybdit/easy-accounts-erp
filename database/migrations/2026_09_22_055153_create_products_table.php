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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('product_category_id')->constrained('product_categories')->restrictOnDelete();
            $table->enum('type', ['inventory', 'service']);
            $table->string('unit')->default('pcs');
            $table->decimal('purchase_price', 19, 4)->default(0);
            $table->decimal('selling_price', 19, 4);
            $table->foreignId('income_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->decimal('low_stock_threshold', 19, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
