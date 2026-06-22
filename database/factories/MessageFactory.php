<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'messageable_type' => (new Customer)->getMorphClass(),
            'messageable_id' => Customer::factory(),
            'author_type' => (new User)->getMorphClass(),
            'author_id' => User::factory(),
            'content' => '<p>'.$this->faker->paragraph().'</p>',
        ];
    }

    /**
     * Attach the message to the given messageable model (customer, etc.).
     */
    public function forMessageable(Model $messageable): static
    {
        return $this->state(fn (array $attributes): array => [
            'messageable_type' => $messageable->getMorphClass(),
            'messageable_id' => $messageable->getKey(),
        ]);
    }

    /**
     * Attribute the message to the given user or customer author.
     */
    public function byAuthor(Model $author): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->getKey(),
        ]);
    }
}
