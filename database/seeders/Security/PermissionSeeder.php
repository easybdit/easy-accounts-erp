<?php

namespace Database\Seeders\Security;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Two-tier permissions per module (view / manage) matching modules that
 * actually exist in the app (Section 36: "final permissions must match
 * actual modules" — nothing speculative like settings.* is included,
 * since no Settings module has been built).
 *
 * "manage" covers create/update/delete/post; "view" covers index/show.
 * Reports are inherently read-only, so they get no "manage" permission.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'accounts', 'journal', 'customers', 'vendors', 'invoices', 'bills',
            'payments', 'expenses', 'banking', 'inventory', 'tax', 'users', 'roles',
        ];

        foreach ($modules as $module) {
            Permission::findOrCreate("{$module}.view");
            Permission::findOrCreate("{$module}.manage");
        }

        Permission::findOrCreate('dashboard.view');
        Permission::findOrCreate('reports.view');
        Permission::findOrCreate('audit.view');
    }
}
