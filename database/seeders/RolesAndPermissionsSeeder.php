<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions ────────────────────────────────────────────────────

        $permissionsByModule = [
            'product' => [
                'product.view', 'product.create', 'product.edit', 'product.delete',
                'product.approve', 'product.feature',
            ],
            'order' => [
                'order.view', 'order.create', 'order.edit', 'order.delete',
                'order.confirm', 'order.ship', 'order.deliver', 'order.cancel',
            ],
            'category' => [
                'category.view', 'category.create', 'category.edit', 'category.delete',
            ],
            'user' => [
                'user.view', 'user.create', 'user.edit', 'user.delete',
            ],
            'role' => [
                'role.view', 'role.create', 'role.edit', 'role.delete',
            ],
            'report' => [
                'report.view', 'report.generate', 'report.export',
            ],
            'setting' => [
                'setting.view', 'setting.edit',
            ],
            'review' => [
                'review.view', 'review.create', 'review.moderate',
            ],
            'inventory' => [
                'inventory.view', 'inventory.edit', 'inventory.stock-adjust',
            ],
            'banner' => [
                'banner.view', 'banner.create', 'banner.edit', 'banner.delete',
            ],
            'cms' => [
                'cms.view', 'cms.create', 'cms.edit', 'cms.delete',
            ],
            'seller' => [
                'seller.view', 'seller.approve', 'seller.suspend',
            ],
            'supplier' => [
                'supplier.view', 'supplier.approve', 'supplier.suspend',
            ],
            'notification' => [
                'notification.view', 'notification.send',
            ],
        ];

        $allPermissions = collect($permissionsByModule)->flatten()->toArray();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['guard_name' => 'web', 'name' => $permission]);
        }

        $this->command->info('Permissions ready: ' . count($allPermissions));

        // ─── Roles ──────────────────────────────────────────────────────────

        $rolePermissions = [
            'super-admin' => $allPermissions,
            'admin'       => $allPermissions,
            'seller'      => [
                'product.view', 'product.create', 'product.edit', 'product.delete',
                'inventory.view', 'inventory.edit',
                'order.view', 'order.edit',
                'review.view',
                'notification.view',
                'report.view', 'report.generate',
            ],
            'supplier'    => [
                'product.view', 'product.create', 'product.edit',
                'inventory.view', 'inventory.edit', 'inventory.stock-adjust',
                'order.view',
                'notification.view',
                'report.view', 'report.generate',
            ],
            'customer'    => [
                'product.view',
                'order.view', 'order.create',
                'review.view', 'review.create',
                'notification.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['guard_name' => 'web', 'name' => $roleName]);
            $role->syncPermissions($permissions);
        }

        $this->command->info('Roles ready: ' . implode(', ', array_keys($rolePermissions)));

        // ─── Default Users ──────────────────────────────────────────────────

        $defaultUsers = [
            [
                'name'      => 'Admin',
                'lastname'  => 'User',
                'username'  => 'admin',
                'email'     => 'admin@oceanovia.com',
                'phone'     => '+1 (555) 000-0001',
                'password'  => 'Password@123',
                'role_type' => 'admin',
                'status'            => 'active',
                'email_verified_at' => now(),
                'assign'            => 'super-admin',
            ],
            [
                'name'      => 'John',
                'lastname'  => 'Seller',
                'username'  => 'johnseller',
                'email'     => 'seller@oceanovia.com',
                'phone'     => '+1 (555) 000-0002',
                'password'  => 'Password@123',
                'role_type' => 'seller',
                'status'            => 'active',
                'email_verified_at' => now(),
                'assign'            => 'seller',
            ],
            [
                'name'      => 'Jane',
                'lastname'  => 'Supplier',
                'username'  => 'janesupplier',
                'email'     => 'supplier@oceanovia.com',
                'phone'     => '+1 (555) 000-0003',
                'password'  => 'Password@123',
                'role_type' => 'supplier',
                'status'            => 'active',
                'email_verified_at' => now(),
                'assign'            => 'supplier',
            ],
            [
                'name'      => 'Bob',
                'lastname'  => 'Customer',
                'username'  => 'bobcustomer',
                'email'     => 'customer@oceanovia.com',
                'phone'     => '+1 (555) 000-0004',
                'password'  => 'Password@123',
                'role_type' => 'customer',
                'status'            => 'active',
                'email_verified_at' => now(),
                'assign'            => 'customer',
            ],
        ];

        foreach ($defaultUsers as $userData) {
            $assign = $userData['assign'];
            unset($userData['assign']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData,
            );

            if (! $user->hasRole($assign)) {
                $user->assignRole($assign);
            }
        }

        $this->command->info('Default users created:');
        $this->command->info('  admin@oceanovia.com / Password@123');
        $this->command->info('  seller@oceanovia.com / Password@123');
        $this->command->info('  supplier@oceanovia.com / Password@123');
        $this->command->info('  customer@oceanovia.com / Password@123');
    }
}



