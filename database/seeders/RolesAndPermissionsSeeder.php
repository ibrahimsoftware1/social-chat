<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // User management
            'view users',
            'edit users',
            'delete users',
            'ban users',
            'unban users',

            // Conversation management
            'create conversations',
            'view any conversation',
            'edit any conversation',
            'delete any conversation',
            'manage conversation participants',

            // Message management
            'delete any message',
            'edit any message',
            'pin messages',

            // Moderation
            'moderate content',
            'view reports',
            'handle reports',
            'warn users',

            // Admin
            'access admin panel',
            'view analytics',
            'manage roles',
        ];

        // Create or update permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. Admin - full access
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // 2. Moderator - extensive moderation permissions
        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->syncPermissions([
            // User management
            'view users',
            'ban users',
            'unban users',
            'warn users',

            // Conversation management
            'view any conversation',
            'edit any conversation',
            'delete any conversation',
            'manage conversation participants',

            // Message management
            'delete any message',
            'edit any message',
            'pin messages',

            // Moderation
            'moderate content',
            'view reports',
            'handle reports',

            // Basic
            'create conversations',
        ]);

        // 3. User - basic permissions
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([
            'create conversations',
        ]);

        // Display results
        $this->displayResults();
    }

    private function displayResults(): void
    {
        $this->command->info('✅ Roles and Permissions created successfully!');
        $this->command->newLine();

        // Display roles and their permissions
        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $this->command->info("📝 Role: {$role->name}");
            $this->command->info("   Permissions: " . $role->permissions->pluck('name')->implode(', '));
            $this->command->newLine();
        }

        // Summary table
        $this->command->table(
            ['Role', 'Permission Count'],
            [
                ['Admin', Role::findByName('admin')->permissions->count()],
                ['Moderator', Role::findByName('moderator')->permissions->count()],
                ['User', Role::findByName('user')->permissions->count()],
            ]
        );
    }
}
