<?php

namespace Database\Seeders;

use App\Enum\UserRole;
use App\Models\Login;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;
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

        $this->seedFeaturedSuperAdmins();

        echo "\nAdding Users\n";

        $super_admins = User::role(UserRole::SuperAdmin->value)->get();
        for ($index = 0; $index < 50; $index++) {
            $super_admin_user = $super_admins->random();
            $created_at = fake()->dateTimeBetween($super_admin_user->created_at, 'yesterday');

            // Attribute this user's creation activity to a random Super Admin.
            app(CauserResolver::class)->setCauser($super_admin_user);

            do {
                $character = RickAndMortyCharacters::next();
                $first_name = preg_replace('/[^A-Za-z0-9]/', '', ucwords($character['first_name']));
                $last_name = preg_replace('/[^A-Za-z0-9]/', '', ucwords($character['last_name']));
            } while (FilterData::hasBadWords($first_name.' '.$last_name));

            $role = match (true) {
                $index <= 10 => UserRole::Admin->value,
                default => UserRole::Staff->value,
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

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);

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
            ['id' => 128, 'first_name' => 'Frankenstien\'s', 'last_name' => 'Monster'],
            ['id' => 280, 'first_name' => 'Reverse', 'last_name' => 'Giraffe'],
        ];

        foreach ($featured_characters as $character) {
            $created_at = fake()->dateTimeBetween('-5 year', '-1 year');
            $user = User::factory()
                ->create([
                    'role' => UserRole::SuperAdmin->value,
                    'first_name' => $character['first_name'],
                    'last_name' => $character['last_name'],
                    'email' => str($character['first_name'].'.'.$character['last_name'].'@example.com')
                        ->lower()
                        ->remove([' ', '\'']),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ])->assignRole(UserRole::SuperAdmin->value);

            $user->addMedia(RickAndMortyCharacters::avatarPath($character['id']))
                ->preservingOriginal()
                ->toMediaCollection('avatar');

            Login::factory(rand(0, 200))->create(['user_id' => $user->id]);
        }
    }
}
