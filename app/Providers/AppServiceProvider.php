<?php

namespace App\Providers;

use App\Models\Accounting\AccountingSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->applySettingsOverrides();
    }

    /**
     * Company Settings (mail + SSLCommerz) are stored in the database so an
     * administrator can change them from the UI instead of editing .env on
     * the server. Both are still config()-driven everywhere else in the
     * app (SslCommerzClient, Laravel's Mail facade) — this is the one place
     * that layers the DB values on top of .env at the start of every
     * request, so nothing else needs to know the settings even come from a
     * database. Left untouched (falls back to .env) whenever a field is
     * blank, and skipped entirely before the table exists (a fresh install
     * running its first migration) or if the DB isn't reachable yet.
     */
    private function applySettingsOverrides(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            // Artisan commands (migrate, queue:work, schedule:run, ...) load
            // this provider before the database is necessarily ready — e.g.
            // `migrate` on a brand new install. Queue workers and the
            // scheduler *do* need these overrides (mail, SSLCommerz), so
            // only skip when the table genuinely isn't there yet.
            try {
                if (! Schema::hasTable('accounting_settings')) {
                    return;
                }
            } catch (Throwable) {
                return;
            }
        }

        try {
            $settings = AccountingSettings::current();
        } catch (Throwable) {
            // No DB connection yet, or the table/columns are mid-migration.
            return;
        }

        if ($settings->mailIsConfigured()) {
            Config::set('mail.default', $settings->mail_mailer ?: 'smtp');
            Config::set('mail.mailers.smtp.host', $settings->mail_host);
            Config::set('mail.mailers.smtp.port', $settings->mail_port ?: 587);
            Config::set('mail.mailers.smtp.username', $settings->mail_username);
            Config::set('mail.mailers.smtp.password', $settings->mail_password);
            Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption ?: null);

            if (filled($settings->mail_from_address)) {
                Config::set('mail.from.address', $settings->mail_from_address);
                Config::set('mail.from.name', $settings->mail_from_name ?: config('app.name'));
            }
        }

        if (filled($settings->sslcommerz_store_id)) {
            Config::set('services.sslcommerz.store_id', $settings->sslcommerz_store_id);
            Config::set('services.sslcommerz.store_password', $settings->sslcommerz_store_password);
            Config::set('services.sslcommerz.sandbox', $settings->sslcommerz_sandbox);

            if (filled($settings->sslcommerz_currency)) {
                Config::set('services.sslcommerz.currency', $settings->sslcommerz_currency);
            }
        }

        Config::set('services.sslcommerz.enabled', $settings->sslcommerzIsConfigured());
    }
}
