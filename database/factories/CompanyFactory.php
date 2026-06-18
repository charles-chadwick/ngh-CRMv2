<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $created_by_user = User::inRandomOrder()->first();
        $created_at = fake()->dateTimeBetween($created_by_user->created_at, '-1 year');

        return [
            'title' => $this->faker->company(),
            'company_type_id' => CompanyType::inRandomOrder()->first()->id,
            'description' => $this->faker->paragraph(),
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ];
    }
}
