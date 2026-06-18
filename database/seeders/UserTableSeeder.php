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

        $this->seedFeaturedSuperAdmins();

        echo "\nAdding Users\n";

        for ($index = 0; $index < 50; $index++) {
            $created_at = fake()->dateTimeBetween($super_admin_user->created_at, '-1 year');

            do {
                $character = RickAndMortyCharacters::next();
                $first_name = $character['first_name'];
                $last_name = $character['last_name'];
            } while (FilterData::hasBadWords($first_name.' '.$last_name));

            $role = match (true) {
                $index <= 10 => UserRole::Admin->value,
                default => UserRole::Staff->value,
            };

            $user = User::factory()
                ->create([
                    'role' => $role,
                    'first_name' => ucwords($first_name),
                    'last_name' => ucwords($last_name),
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

    /**
     * Always seed these Rick and Morty characters as Super Admins.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    private function seedFeaturedSuperAdmins(): void
    {
        $featured_characters = [
            ['id' => 328, 'first_name' => 'Slow', 'last_name' => 'Rick'],
            ['id' => 103, 'first_name' => 'Doofus', 'last_name' => 'Rick'],
            ['id' => 347, 'first_name' => 'President', 'last_name' => 'Curtis'],
            ['id' => 128, 'first_name' => 'Frankenstien', 'last_name' => 'Monster'],
            ['id' => 280, 'first_name' => 'Reverse', 'last_name' => 'Giraffe'],
        ];

        foreach ($featured_characters as $character) {
            $user = User::factory()
                ->create([
                    'role' => UserRole::SuperAdmin->value,
                    'first_name' => $character['first_name'],
                    'last_name' => $character['last_name'],
                    'email' => str($character['first_name'].'.'.$character['last_name'].'@example.com')
                        ->lower()
                        ->remove([' ', '\'']),
                ])->assignRole(UserRole::SuperAdmin->value);

            $user->addMedia(RickAndMortyCharacters::avatarPath($character['id']))
                ->preservingOriginal()
                ->toMediaCollection('avatar');

            Login::factory(rand(0, 200))->create(['user_id' => $user->id]);
        }
    }
}
