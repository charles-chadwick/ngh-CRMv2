<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Seeder;
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

        echo "\nAdding Customers\n";

        for ($index = 0; $index < 450; $index++) {
            $created_at = fake()->dateTimeBetween('-5 years', '-1 year');

            do {
                $character = RickAndMortyCharacters::next();
                $first_name = $character['first_name'];
                $last_name = $character['last_name'];
            } while (FilterData::hasBadWords($first_name.' '.$last_name));

            $customer = Customer::factory()
                ->create([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => str($first_name.'.'.$last_name.rand(1, 9999).'@example.com')
                        ->lower()
                        ->remove([' ', '\'']),
                    'company_id' => $companies->random()->id,
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);

            $customer->addMedia(RickAndMortyCharacters::avatarPath($character['id']))
                ->preservingOriginal()
                ->toMediaCollection('avatar');

            echo '.';
        }

        echo "\n";
    }
}
