<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            // Email (SMTP) — null means "fall back to .env". Values here
            // override config('mail.*') at runtime (see AppServiceProvider).
            $table->string('mail_mailer')->nullable()->after('logo_path');
            $table->string('mail_host')->nullable()->after('mail_mailer');
            $table->unsignedSmallInteger('mail_port')->nullable()->after('mail_host');
            $table->string('mail_username')->nullable()->after('mail_port');
            $table->text('mail_password')->nullable()->after('mail_username');
            $table->string('mail_encryption')->nullable()->after('mail_password');
            $table->string('mail_from_address')->nullable()->after('mail_encryption');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');

            // SSLCommerz — overrides config('services.sslcommerz.*') when set.
            $table->boolean('sslcommerz_enabled')->default(false)->after('mail_from_name');
            $table->string('sslcommerz_store_id')->nullable()->after('sslcommerz_enabled');
            $table->text('sslcommerz_store_password')->nullable()->after('sslcommerz_store_id');
            $table->boolean('sslcommerz_sandbox')->default(true)->after('sslcommerz_store_password');
            $table->string('sslcommerz_currency')->nullable()->after('sslcommerz_sandbox');

            // Login security — brute-force account lock and the math captcha.
            $table->unsignedTinyInteger('login_max_attempts')->default(5)->after('sslcommerz_currency');
            $table->unsignedSmallInteger('login_lockout_minutes')->default(15)->after('login_max_attempts');
            $table->boolean('login_captcha_enabled')->default(true)->after('login_lockout_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password',
                'mail_encryption', 'mail_from_address', 'mail_from_name',
                'sslcommerz_enabled', 'sslcommerz_store_id', 'sslcommerz_store_password',
                'sslcommerz_sandbox', 'sslcommerz_currency',
                'login_max_attempts', 'login_lockout_minutes', 'login_captcha_enabled',
            ]);
        });
    }
};
