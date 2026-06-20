<?php

use App\Models\Customer;
use App\Models\User;
use App\Notifications\UserMentionedInNote;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends the mention over the broadcast channel', function () {
    Notification::fake();

    $author = User::factory()->create();
    $jane = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($author)->post(route('notes.store'), [
        'model_type' => 'customer',
        'model_id' => $customer->id,
        'type' => 'General',
        'title' => 'Note',
        'content' => "<p>hi <span data-id=\"{$jane->id}\">@Jane</span></p>",
    ]);

    Notification::assertSentTo(
        $jane,
        UserMentionedInNote::class,
        fn ($notification, array $channels): bool => in_array('broadcast', $channels, true),
    );
});

it('authorizes the channel owner but rejects others', function () {
    // Use the reverb (Pusher-compatible) broadcaster so channel authorization
    // is enforced. The Pusher SDK signs auth responses with local HMAC —
    // no running Reverb server is required. We purge the null driver, switch
    // to reverb with fake credentials, and re-register the channel so the
    // new driver instance picks it up.
    Config::set('broadcasting.connections.reverb.key', 'test-key');
    Config::set('broadcasting.connections.reverb.secret', 'test-secret');
    Config::set('broadcasting.connections.reverb.app_id', 'test-app-id');
    Config::set('broadcasting.connections.reverb.options.host', 'localhost');
    Config::set('broadcasting.connections.reverb.options.port', 8080);
    Config::set('broadcasting.connections.reverb.options.scheme', 'http');
    Config::set('broadcasting.connections.reverb.options.useTLS', false);

    /** @var BroadcastManager $manager */
    $manager = app('Illuminate\Broadcasting\BroadcastManager');
    $manager->purge('null');
    $manager->setDefaultDriver('reverb');

    // Re-register the channel on the new reverb broadcaster instance.
    Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
        return (int) $user->id === $id;
    });

    $user = User::factory()->create();
    $other = User::factory()->create();

    try {
        // The owner may authorize their own private channel.
        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-App.Models.User.{$user->id}",
                'socket_id' => '1234.5678',
            ])
            ->assertOk();

        // A different user may not authorize someone else's channel.
        $this->actingAs($other)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-App.Models.User.{$user->id}",
                'socket_id' => '1234.5678',
            ])
            ->assertForbidden();
    } finally {
        // Restore the default broadcaster so subsequent tests are not affected.
        $manager->purge('reverb');
        $manager->setDefaultDriver('null');
    }
});
