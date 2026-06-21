<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

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
        $this->seedContact($parent, primary: true);

        $extra_count = rand(0, 2);

        for ($i = 0; $i < $extra_count; $i++) {
            $this->seedContact($parent);
        }

        echo '.';
    }

    /**
     * Create a single contact, attributing it to a random existing user so the
     * activity log records a causer (mirrors {@see NoteSeeder}).
     */
    private function seedContact(Model $parent, bool $primary = false): void
    {
        $factory = Contact::factory()->forContactable($parent);

        if ($primary) {
            $factory = $factory->primary();
        }

        $created_by_user = User::inRandomOrder()->first();

        if ($created_by_user === null) {
            $factory->create();

            return;
        }

        $created_at = fake()->dateTimeBetween($created_by_user->created_at);
        app(CauserResolver::class)->setCauser($created_by_user);

        $factory->create(['created_at' => $created_at, 'updated_at' => $created_at]);
    }
}
