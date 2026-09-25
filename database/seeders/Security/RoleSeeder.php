<?php

namespace Database\Seeders\Security;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Section 36's example roles, trimmed to ones that are actually meaningful
 * under this app's two-tier (view/manage) permission model. "Manager" was
 * dropped: with only two permission levels, a broad-visibility/no-write
 * role is indistinguishable from "Viewer", so keeping both would just be
 * two names for the same set of permissions (Section 36: "do not create
 * roles blindly"). These are still examples, not a fixed policy — adjust
 * per real organizational needs before go-live.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = Role::findOrCreate('Administrator');
        $administrator->syncPermissions(Permission::all());

        Role::findOrCreate('Accountant')->syncPermissions([
            'dashboard.view', 'reports.view',
            'accounts.view', 'accounts.manage',
            'journal.view', 'journal.manage',
            'customers.view', 'customers.manage',
            'vendors.view', 'vendors.manage',
            'invoices.view', 'invoices.manage',
            'bills.view', 'bills.manage',
            'payments.view', 'payments.manage',
            'expenses.view', 'expenses.manage',
            'banking.view', 'banking.manage',
            'tax.view', 'tax.manage',
            'settings.view', 'settings.manage',
            'payroll.view', 'payroll.manage',
        ]);

        Role::findOrCreate('Sales')->syncPermissions([
            'dashboard.view', 'reports.view',
            'customers.view', 'customers.manage',
            'invoices.view', 'invoices.manage',
            'payments.view', 'payments.manage',
        ]);

        Role::findOrCreate('Purchase')->syncPermissions([
            'dashboard.view', 'reports.view',
            'vendors.view', 'vendors.manage',
            'bills.view', 'bills.manage',
            'payments.view', 'payments.manage',
        ]);

        Role::findOrCreate('Inventory')->syncPermissions([
            'dashboard.view', 'reports.view',
            'inventory.view', 'inventory.manage',
        ]);

        Role::findOrCreate('Viewer')->syncPermissions(
            Permission::where('name', 'like', '%.view')->pluck('name')
        );

        // Self-service only: an employee logs in through the normal login
        // and sees just their own leave/payroll data, never module-wide
        // "leaves.view"/"payroll.view".
        Role::findOrCreate('Employee')->syncPermissions([
            'dashboard.view', 'leaves.own', 'payroll.own',
        ]);
    }
}
