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
        Schema::create('withholding_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('rate', 8, 4);
            $table->foreignId('liability_account_id')->constrained('accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_rates');
    }
};
