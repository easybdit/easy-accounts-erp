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
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->decimal('disposal_proceeds', 19, 4)->nullable()->after('disposal_notes');
            $table->foreignId('disposal_proceeds_account_id')->nullable()->after('disposal_proceeds')
                ->constrained('accounts', indexName: 'fixed_assets_disposal_proceeds_fk')->nullOnDelete();
            $table->foreignId('gain_loss_account_id')->nullable()->after('disposal_proceeds_account_id')
                ->constrained('accounts', indexName: 'fixed_assets_gain_loss_fk')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disposal_proceeds_account_id');
            $table->dropConstrainedForeignId('gain_loss_account_id');
            $table->dropColumn('disposal_proceeds');
        });
    }
};
