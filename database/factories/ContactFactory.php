<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(Contact::TYPES),
            'is_primary' => false,
            'street_address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'work_phone' => $this->faker->phoneNumber(),
            'mobile_phone' => $this->faker->phoneNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Attach the contact to the given contactable model (customer, company, etc.).
     */
    public function forContactable(Model $contactable): static
    {
        return $this->state(fn (array $attributes) => [
            'contactable_type' => $contactable->getMorphClass(),
            'contactable_id' => $contactable->getKey(),
        ]);
    }

    /**
     * Mark the contact as the primary one for its parent.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => ['is_primary' => true]);
    }
}
