<?php

namespace Database\Seeders;

use App\Enum\UserRole;
use Illuminate\Contracts\Container\BindingResolutionException;
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

    /**
     * @throws BindingResolutionException
     */
    public function run(): void
    {
        App::make(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $permissions = $this->createPermissions();

        $admin = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value])
            ->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => UserRole::Admin->value]);
        $admin->syncPermissions($permissions);

        $manager = Role::firstOrCreate(['name' => UserRole::Staff->value]);
        $manager->syncPermissions(
            $permissions->reject(fn (Permission $permission) => str_starts_with($permission->name, 'delete '))
        );

        $customer = Role::firstOrCreate(['name' => UserRole::Customer->value]);
        $customer->syncPermissions(
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
