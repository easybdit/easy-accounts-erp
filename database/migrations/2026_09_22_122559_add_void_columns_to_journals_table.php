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
        Schema::table('journals', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('posted_at');
            $table->foreignId('reversal_of_journal_id')->nullable()->after('voided_at')
                ->constrained('journals')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_journal_id');
            $table->dropColumn('voided_at');
        });
    }
};
