<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Message;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $customers = Customer::all();

        if ($users->isEmpty() || $customers->isEmpty()) {
            return;
        }

        echo "\nAdding Topics\n";

        // Give every user at least one conversation so any seeded login lands
        // on a populated inbox.
        foreach ($users as $starter) {
            foreach (range(1, rand(1, 2)) as $i) {
                $participants = $this->participantsFor($users, $customers, $starter);

                app(CauserResolver::class)->setCauser($starter);

                $topic = Topic::factory()->create();
                $this->attachParticipants($topic, $participants);
                $this->seedMessagesFor($topic, $participants);

                echo '.';
            }
        }

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);

        echo "\n";
    }

    /**
     * Build a participant list: the starter plus one or more other users/customers.
     *
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Customer>  $customers
     * @return list<Model>
     */
    private function participantsFor($users, $customers, User $starter): array
    {
        $participants = [$starter, $customers->random()];

        if ($users->count() > 1 && fake()->boolean()) {
            $participants[] = $users->where('id', '!=', $starter->id)->random();
        }

        return $participants;
    }

    /**
     * @param  list<Model>  $participants
     */
    private function attachParticipants(Topic $topic, array $participants): void
    {
        foreach ($participants as $participant) {
            $relation = $participant instanceof User ? $topic->users() : $topic->customers();
            $relation->syncWithoutDetaching($participant->getKey());
        }
    }

    /**
     * @param  list<Model>  $participants
     */
    private function seedMessagesFor(Topic $topic, array $participants): void
    {
        foreach (range(1, rand(2, 6)) as $j) {
            $author = $participants[array_rand($participants)];

            app(CauserResolver::class)->setCauser($author instanceof User ? $author : null);

            Message::factory()->forTopic($topic)->byAuthor($author)->create();
        }
    }
}
