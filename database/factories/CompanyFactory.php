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

        return [
            'title' => $this->faker->company(),
            'company_type_id' => CompanyType::inRandomOrder()->first()?->id
                ?? CompanyType::create(['name' => $this->faker->unique()->word()])->id,
            'description' => $this->faker->paragraph(),
        ];
    }
}
