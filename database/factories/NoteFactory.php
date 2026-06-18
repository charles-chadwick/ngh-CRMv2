<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $created_at = $this->faker->dateTimeBetween('-5 years', 'now');

        return [
            'notable_type' => Customer::class,
            'notable_id' => Customer::factory(),
            'type' => $this->faker->randomElement(Note::TYPES),
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'created_at' => $created_at,
            'updated_at' => $created_at,
            'deleted_at' => null,
        ];
    }

    /**
     * Attach the note to the given notable model (customer, company, etc.).
     */
    public function forNotable(Model $notable): static
    {
        return $this->state(fn (array $attributes) => [
            'notable_type' => $notable->getMorphClass(),
            'notable_id' => $notable->getKey(),
        ]);
    }
}
