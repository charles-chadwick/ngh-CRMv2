<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        echo "\nAdding Notes\n";

        Customer::all()->each(function (Customer $customer): void {
            $this->seedNotesFor($customer);
            echo '.';
        });

        Company::all()->each(function (Company $company): void {
            $this->seedNotesFor($company);
            echo '.';
        });

        echo "\n";
    }

    private function seedNotesFor(Model $notable): void
    {
        $note_count = rand(0, 5);

        for ($i = 0; $i < $note_count; $i++) {
            $created_by_user = User::inRandomOrder()->first();

            if ($created_by_user === null) {
                return;
            }

            $created_at = fake()->dateTimeBetween($created_by_user->created_at);
            app(CauserResolver::class)->setCauser($created_by_user);
            Note::factory()->forNotable($notable)->create(['created_at' => $created_at, 'updated_at' => $created_at]);
        }
    }
}
