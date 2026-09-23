<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            // Map of document type key => prefix override, e.g.
            // {"invoice": "INV", "sales_receipt": "SR"}. A key missing from
            // this map falls back to GenerateDocumentNumber::DEFAULTS
            // (Section 50) — every existing document number stays unchanged
            // until an admin explicitly overrides a prefix in Settings.
            $table->json('document_number_prefixes')->nullable()->after('ip_whitelist_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('document_number_prefixes');
        });
    }
};
