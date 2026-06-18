<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition() : array
    {
        $created_at = $this->faker->dateTimeBetween('-5 years', 'now');

        return [
            'type'       => $this->faker->word(),

            'title'      => $this->faker->word(),
            'content'    => $this->faker->paragraphs(),
            'created_at' => $created_at,
            'updated_at' => $created_at,
            'deleted_at' => null,
        ];
    }
}
