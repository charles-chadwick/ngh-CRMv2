<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'street_address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->word(),
            'zip' => $this->faker->postcode(),
            'work_phone' => $this->faker->phoneNumber(),
            'mobile_phone' => $this->faker->phoneNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
