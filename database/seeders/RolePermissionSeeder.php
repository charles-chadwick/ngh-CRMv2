<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * The permissions to create, grouped by resource.
     *
     * @var array<string, list<string>>
     */
    private array $permissionsByResource = [
        'users' => [
            'view',
            'create',
            'update',
            'delete',
        ],
        'companies' => [
            'view',
            'create',
            'update',
            'delete',
        ],
    ];

    public function run(): void
    {
        App::make(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $permissions = $this->createPermissions();

        $admin = Role::firstOrCreate(['name' => 'Super Admin'])
            ->syncPermissions($permissions);

        $admin->syncPermissions($permissions);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions($permissions);

        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $manager->syncPermissions(
            $permissions->reject(fn (Permission $permission) => str_starts_with($permission->name, 'delete '))
        );

        $salesRep = Role::firstOrCreate(['name' => 'Staff']);
        $salesRep->syncPermissions(
            $permissions->filter(fn (Permission $permission) => str_starts_with($permission->name, 'view '))
        );
    }

    /**
     * Create every "{action} {resource}" permission.
     *
     * @return Collection<int, Permission>
     */
    private function createPermissions(): Collection
    {
        return collect($this->permissionsByResource)
            ->flatMap(function (array $actions, string $resource) {
                return collect($actions)->map(
                    fn (string $action) => Permission::firstOrCreate(['name' => "{$action} {$resource}"])
                );
            });
    }
}
