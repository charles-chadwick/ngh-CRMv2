<?php

namespace Database\Factories;

use App\Models\Login;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LoginFactory extends Factory
{
    protected $model = Login::class;

    public function definition() : array
    {
        return [
            'ip_address' => $this->faker->ipv4(),
            'created_at' => Carbon::parse(fake()->dateTimeBetween('-5 years', 'now')),
        ];
    }
}
