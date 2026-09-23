<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_whitelist_entries', function (Blueprint $table) {
            $table->id();
            // A single IP ("203.0.113.7") or CIDR range ("203.0.113.0/24") —
            // matched against the request IP in EnsureIpIsWhitelisted.
            $table->string('ip_address');
            $table->string('label')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_whitelist_entries');
    }
};
