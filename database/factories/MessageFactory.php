<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Topic;
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
            'topic_id' => Topic::factory(),
            'author_type' => User::class,
            'author_id' => User::factory(),
            'content' => '<p>'.$this->faker->paragraph().'</p>',
        ];
    }

    /**
     * Attach the message to the given topic.
     */
    public function forTopic(Topic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'topic_id' => $topic->getKey(),
        ]);
    }

    /**
     * Attribute the message to the given user or customer author.
     */
    public function byAuthor(Model $author): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->getKey(),
        ]);
    }
}
