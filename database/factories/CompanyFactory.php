<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $created = $this->faker->dateTimeBetween('-5 years', 'now');

        return [
            'title' => $this->faker->company(),
            'company_type_id' => CompanyType::inRandomOrder()->first()->id,
            'description' => $this->faker->paragraph(),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }
}
