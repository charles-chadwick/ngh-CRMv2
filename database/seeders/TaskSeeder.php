<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $assignee_ids = User::pluck('id');

        if ($assignee_ids->isEmpty()) {
            return;
        }

        $subjects = collect()
            ->concat(Company::inRandomOrder()->limit(5)->get())
            ->concat(Customer::inRandomOrder()->limit(5)->get())
            ->concat(Lead::inRandomOrder()->limit(5)->get());

        if ($subjects->isEmpty()) {
            return;
        }

        foreach ($subjects as $subject) {
            foreach (['overdue', 'dueToday', 'upcoming', 'completed'] as $bucket) {
                $factory = Task::factory()
                    ->forSubject($subject)
                    ->assignedTo(User::find($assignee_ids->random()));

                $factory = match ($bucket) {
                    'overdue' => $factory->overdue(),
                    'dueToday' => $factory->dueToday(),
                    'completed' => $factory->completed(),
                    default => $factory,
                };

                $factory->create();
            }
        }
    }
}
