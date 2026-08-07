<?php

namespace Database\Seeders;

use App\Enum\UserRole;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerRole;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

use function fake;

class CustomerSeeder extends Seeder
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function run(): void
    {
        $companies = Company::all();
        $roles = CustomerRole::all();
        $titles = Title::all();

        echo "\nAdding Customers\n";

        $created_by_users = User::where('role', '!=', UserRole::SuperAdmin->value)->get();

        // Use every character that wasn't already assigned to a user.
        foreach (RickAndMortyCharacters::remaining() as $character) {
            $first_name = preg_replace('/[^A-Za-z0-9\s]/', '', ucwords($character['first_name']));
            $last_name = preg_replace('/[^A-Za-z0-9\s]/', '', ucwords($character['last_name']));

            // Drop trailing words while the combined name exceeds 35 characters.
            $words = preg_split('/\s+/', trim($first_name.' '.$last_name));

            // Cap the full name (first + last) at three words.
            while (count($words) > 2) {
                array_pop($words);
            }

            while (count($words) > 1 && strlen(implode(' ', $words)) > 35) {
                array_pop($words);
            }

            // If a single word still exceeds 35 characters, truncate it.
            if (count($words) === 1 && strlen($words[0]) > 35) {
                $words[0] = substr($words[0], 0, 35);
            }

            $first_name = array_shift($words);
            $last_name = implode(' ', $words);

            if (FilterData::hasBadWords($first_name.' '.$last_name)) {
                continue;
            }

            $created_by_user = $created_by_users->random();
            $created_at = fake()->dateTimeBetween($created_by_user->created_at, 'yesterday');

            // Attribute this customer's creation activity to a random user.
            app(CauserResolver::class)->setCauser($created_by_user);

            $customer = Customer::factory()
                ->create([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => str($first_name.'.'.$last_name.rand(1, 9999).'@example.com')
                        ->lower()
                        ->remove([' ', '\'']),
                    'company_id' => $companies->random()->id,
                    'customer_role_id' => $roles->random()->id,
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);

            $customer->titles()->attach($titles->random(rand(1, 3))->pluck('id'));

            $customer->addMedia(RickAndMortyCharacters::avatarPath($character['id']))
                ->preservingOriginal()
                ->toMediaCollection('avatar');

            echo '.';
        }

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);

        echo "\n";
    }
}
