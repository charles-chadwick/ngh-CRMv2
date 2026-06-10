<?php

namespace Database\Seeders;

use App\Enum\UserRole;
use App\Models\Login;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

use function fake;

class UserTableSeeder extends Seeder
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function run(): void
    {
        $super_admin_user = User::factory()
            ->create([
                'role' => UserRole::SuperAdmin->value,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'created_at' => '2020-01-01 00:00:00',
            ])->assignRole(UserRole::SuperAdmin->value);

        echo "\nAdding Users\n";

        for ($index = 0; $index < 500; $index++) {
            $created_at = fake()->dateTimeBetween($super_admin_user->created_at, '-1 year');

            $character = RickAndMortyCharacters::next();
            $first_name = $character['first_name'];
            $last_name = $character['last_name'];

            $role = match (true) {
                $index <= 10  => UserRole::Admin->value,
                $index <= 50  => UserRole::Staff->value,
                default      => UserRole::Customer->value,
            };

            $user = User::factory()
                ->create([
                    'role' => $role,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => str($first_name.'.'.$last_name.rand(1, 9999).'@example.com')
                        ->lower()
                        ->remove([' ', '\'']),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ])->assignRole($role);

            $user->addMedia(RickAndMortyCharacters::avatarPath($character['id']))
                ->preservingOriginal()
                ->toMediaCollection('avatar');

            Login::factory(rand(0, 200))->create(['user_id' => $user->id]);

            echo '.';
        }

        echo "\n";
    }
}
