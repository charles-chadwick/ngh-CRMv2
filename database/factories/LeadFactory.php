<?php

namespace Database\Factories;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'status' => fake()->randomElement(LeadStatus::cases()),
            'title' => fake()->words(3, true),
            'type' => fake()->randomElement(LeadType::cases()),
            'initiated_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'assigned_to' => User::inRandomOrder()->first()?->id,
        ];
    }
}
