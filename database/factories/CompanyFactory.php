<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $created = $this->faker->dateTimeBetween('-5 years', 'now');
        return [
            'title' => $this->faker->company(),
            'type' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'created_at' => $created,
            'updated_at' => $created
        ];
    }
}
