<?php

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('topics.{topic}', function (User $user, Topic $topic): bool {
    return $topic->users()->whereKey($user->id)->exists();
});
