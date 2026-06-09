<?php

namespace Database\Seeders;

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
        $admin_user = User::factory()
            ->create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'created_at' => '2020-01-01 00:00:00',
            ]);

        $admin_user->assignRole('Super Admin');

        echo "\nAdding Users\n";

        for ($index = 0; $index < 25; $index++) {
            $created_at = fake()->dateTimeBetween($admin_user->created_at, '-1 year');

            $character = RickAndMortyCharacters::next();
            $first_name = $character['first_name'];
            $last_name = $character['last_name'];

            $staff_user = User::factory()
                ->create([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => str($first_name.'.'.$last_name.rand(100, 999).'@example.com')
                        ->lower()
                        ->remove([' ', '\'']),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);
            $staff_user->assignRole(fake()->randomElement(['Admin',  'Staff', 'Manager']));

            $staff_user->addMedia(RickAndMortyCharacters::avatarPath($character['id']))
                ->preservingOriginal()
                ->toMediaCollection('avatar');

            Login::factory(rand(0, 2000))->create(['user_id' => $staff_user->id]);

            echo '.';
        }

        echo "\n";
    }
}
