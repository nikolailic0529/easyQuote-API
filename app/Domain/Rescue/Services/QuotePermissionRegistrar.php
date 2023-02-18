<?php

namespace App\Domain\Rescue\Services;

use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Notifications\QuoteAccessGrantedNotification;
use App\Domain\Rescue\Notifications\QuoteAccessRevokedNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class QuotePermissionRegistrar
{
    public function handleQuoteGrantedUsers(Quote $quote, array $users): bool
    {
        $granted = Collection::wrap(Arr::get($users, 'granted'))->whereInstanceOf(User::class);
        $revoked = Collection::wrap(Arr::get($users, 'revoked'))->whereInstanceOf(User::class);

        /** @var User $causer */
        $causer = auth()->user();

        Notification::send($granted, new QuoteAccessGrantedNotification($causer, $quote));
        Notification::send($revoked, new QuoteAccessRevokedNotification($causer, $quote));

        return true;
    }
}
