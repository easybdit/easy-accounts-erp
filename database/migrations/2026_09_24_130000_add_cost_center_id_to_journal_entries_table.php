<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reserves the optional tracking-dimension column called for by
     * EasyAccountsERP.md Section 89 (Multi-Vertical Reuse Strategy).
     * No cost_centers table exists yet and no feature reads this column —
     * it is schema-only, so a future Class/Cost Center feature does not
     * require an expensive retrofit across already-posted journal data.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('vendor_id');
            $table->index('cost_center_id');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['cost_center_id']);
            $table->dropColumn('cost_center_id');
        });
    }
};
