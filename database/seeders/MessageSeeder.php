<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        echo "\nAdding Messages\n";

        Customer::all()->each(function (Customer $customer) use ($users): void {
            $this->seedMessagesFor($customer, $users);
            echo '.';
        });

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);

        echo "\n";
    }

    /**
     * Seed a handful of two-sided messages for the customer.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedMessagesFor(Customer $customer, Collection $users): void
    {
        $message_count = rand(0, 5);

        for ($i = 0; $i < $message_count; $i++) {
            $author = fake()->boolean(40) ? $customer : $users->random();
            $created_at = fake()->dateTimeBetween($customer->created_at);

            app(CauserResolver::class)->setCauser($author instanceof User ? $author : null);

            Message::factory()
                ->forMessageable($customer)
                ->byAuthor($author)
                ->create(['created_at' => $created_at, 'updated_at' => $created_at]);
        }
    }
}
