<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        echo "\nAdding Contacts\n";

        Customer::all()->each(fn (Customer $customer) => $this->seedContactsFor($customer));
        Company::all()->each(fn (Company $company) => $this->seedContactsFor($company));

        echo "\n";
    }

    /**
     * Seed 1–3 contacts for the given parent, making the first one primary.
     */
    protected function seedContactsFor(Model $parent): void
    {
        Contact::factory()->forContactable($parent)->primary()->create();
        Contact::factory(rand(0, 2))->forContactable($parent)->create();

        echo '.';
    }
}
