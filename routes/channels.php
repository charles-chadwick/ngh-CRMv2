<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('customers.{customer}', function ($actor, Customer $customer): bool {
    if ($actor instanceof Customer) {
        return (int) $actor->id === (int) $customer->id;
    }

    return true;
}, ['guards' => ['web', 'customer']]);
