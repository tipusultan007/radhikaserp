<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ---------------------------------------------------------------
        // STEP 1: Define ALL granular permissions
        // ---------------------------------------------------------------
        $permissions = [

            // Dashboard
            'view dashboard',

            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Product Variants
            'create product variants',
            'edit product variants',
            'delete product variants',

            // Warehouses
            'view warehouses',
            'create warehouses',
            'edit warehouses',
            'delete warehouses',

            // Customers
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            'export customers',

            // Suppliers
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',

            // Imports (Shipments)
            'view imports',
            'create imports',
            'edit imports',
            'delete imports',

            // Sales / POS
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            'print sales',
            'export sales',

            // Sale Payments
            'view sale payments',
            'edit sale payments',
            'delete sale payments',

            // Repackaging / Production
            'view repackaging',
            'create repackaging',
            'edit repackaging',
            'delete repackaging',

            // Stock Transfers
            'view stock transfers',
            'create stock transfers',
            'edit stock transfers',
            'delete stock transfers',
            'update transfer status',

            // Stock Adjustments
            'view stock adjustments',
            'create stock adjustments',
            'edit stock adjustments',
            'delete stock adjustments',
            'approve stock adjustments',
            
            // Salaries
            'view salaries',
            'manage salaries',

            // Expenses
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',

            // Due Settlements
            'view settlements',
            'settle customer dues',
            'settle supplier payables',

            // Journals / Accounting
            'view journals',
            'create journals',
            'edit journals',
            'delete journals',
            'view chart of accounts',
            'manage chart of accounts',
            'view cashbook',
            'view balance sheet',

            // Reports
            'view stock reports',
            'view sales reports',
            'view production reports',
            'view financial reports',

            // Activity Logs
            'view activity logs',

            // Users & Roles (system admin only)
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ---------------------------------------------------------------
        // STEP 2: Remove old coarse-grained permissions (cleanup)
        // ---------------------------------------------------------------
        $oldPermissions = [
            'manage master data',
            'manage sales',
            'manage imports',
            'manage repackaging',
            'manage stock transfers',
            'manage stock adjustments',
            'manage expenses',
            'manage journals',
            'manage settlements',
            'view reports',
        ];

        foreach ($oldPermissions as $old) {
            $perm = Permission::where('name', $old)->first();
            if ($perm) {
                // Detach from roles before deleting
                $perm->roles()->detach();
                $perm->delete();
            }
        }

        // ---------------------------------------------------------------
        // STEP 3: Create Roles and Assign Permissions
        // ---------------------------------------------------------------

        // ADMIN — Full access to everything
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        // MANAGER — Full operational access, no system settings
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->syncPermissions([
            'view dashboard',

            'view products',
            'create products',
            'edit products',
            'create product variants',
            'edit product variants',

            'view warehouses',

            'view customers',
            'create customers',
            'edit customers',
            'export customers',

            'view suppliers',
            'create suppliers',
            'edit suppliers',

            'view imports',
            'create imports',
            'edit imports',

            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            'print sales',
            'export sales',

            'view sale payments',
            'edit sale payments',
            'delete sale payments',

            'view repackaging',
            'create repackaging',
            'edit repackaging',

            'view stock transfers',
            'create stock transfers',
            'edit stock transfers',
            'update transfer status',

            'view stock adjustments',
            'create stock adjustments',
            'edit stock adjustments',
            'approve stock adjustments',

            'view expenses',
            'create expenses',
            'edit expenses',

            'view salaries',

            'view settlements',
            'settle customer dues',
            'settle supplier payables',

            'view journals',
            'view chart of accounts',
            'view cashbook',
            'view balance sheet',

            'view stock reports',
            'view sales reports',
            'view financial reports',

            'view activity logs',
        ]);

        // CASHIER — POS access only
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);
        $cashierRole->syncPermissions([
            'view dashboard',
            'view products',
            'view customers',
            'create customers',
            'view sales',
            'create sales',
            'print sales',
            'view sale payments',
        ]);

        // WAREHOUSE STAFF — Stock operations only
        $warehouseStaffRole = Role::firstOrCreate(['name' => 'Warehouse Staff']);
        $warehouseStaffRole->syncPermissions([
            'view dashboard',
            'view products',
            'view warehouses',
            'view imports',
            'view stock transfers',
            'create stock transfers',
            'update transfer status',
            'view repackaging',
            'create repackaging',
            'view stock adjustments',
            'create stock adjustments',
            'view stock reports',
        ]);

        // ACCOUNTANT — Financial visibility, journal posting
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant']);
        $accountantRole->syncPermissions([
            'view dashboard',
            'view sales',
            'view imports',
            'view expenses',
            'create expenses',
            'view settlements',
            'view journals',
            'create journals',
            'edit journals',
            'view chart of accounts',
            'view cashbook',
            'view balance sheet',
            'view stock reports',
            'view sales reports',
            'view financial reports',
        ]);

        // ---------------------------------------------------------------
        // STEP 4: Create / update test users
        // ---------------------------------------------------------------

        // Super Admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        if (!$adminUser->hasRole('Admin')) {
            $adminUser->assignRole('Admin');
        }

        // Manager
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name'     => 'Store Manager',
                'password' => Hash::make('password'),
            ]
        );
        if (!$managerUser->hasRole('Manager')) {
            $managerUser->syncRoles(['Manager']);
        }

        // Cashier
        $cashierUser = User::firstOrCreate(
            ['email' => 'cashier@example.com'],
            [
                'name'     => 'Terminal Cashier',
                'password' => Hash::make('password'),
            ]
        );
        if (!$cashierUser->hasRole('Cashier')) {
            $cashierUser->syncRoles(['Cashier']);
        }

        // Warehouse Staff
        $warehouseUser = User::firstOrCreate(
            ['email' => 'warehouse@example.com'],
            [
                'name'     => 'Warehouse Staff',
                'password' => Hash::make('password'),
            ]
        );
        if (!$warehouseUser->hasRole('Warehouse Staff')) {
            $warehouseUser->syncRoles(['Warehouse Staff']);
        }

        // Accountant
        $accountantUser = User::firstOrCreate(
            ['email' => 'accountant@example.com'],
            [
                'name'     => 'Company Accountant',
                'password' => Hash::make('password'),
            ]
        );
        if (!$accountantUser->hasRole('Accountant')) {
            $accountantUser->syncRoles(['Accountant']);
        }

        $this->command->info('✅ Roles & Permissions seeded successfully.');
        $this->command->info('   Roles: Admin, Manager, Cashier, Warehouse Staff, Accountant');
        $this->command->info('   Permissions: ' . Permission::count() . ' total');
    }
}
