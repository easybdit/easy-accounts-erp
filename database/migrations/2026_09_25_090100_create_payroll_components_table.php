<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurable mapping from a salary slip field (a fixed key like
     * basic_salary/net_salary, or a dynamic key inside the slip's
     * `allowances` JSON map) to a Chart of Accounts account. This is what
     * lets payroll posting stay organization/country-agnostic instead of
     * hardcoding any statutory rule.
     */
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['earning', 'deduction']);
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('source_component_key')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
