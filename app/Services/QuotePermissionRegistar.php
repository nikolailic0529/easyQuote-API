<?php

namespace App\Services;

use App\Models\User;
use App\Models\Quote\Quote;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Notifications\GrantedQuoteAccess;
use App\Notifications\RevokedQuoteAccess;

class QuotePermissionRegistar
{
    public function handleQuoteGrantedUsers(Quote $quote, array $users)
    {
        $granted = Collection::wrap(Arr::get($users, 'granted'))->whereInstanceOf(User::class);
        $revoked = Collection::wrap(Arr::get($users, 'revoked'))->whereInstanceOf(User::class);

        $causer = auth()->user();
        
        $grantedMessage = sprintf(
            'User %s has granted you access to Quote RFQ %s',
            optional($causer)->email,
            optional($quote->customer)->rfq
        );
        $revokedMessage = sprintf(
            'User %s has revoked your access to Quote RFQ %s',
            optional($causer)->email,
            optional($quote->customer)->rfq
        );

        $granted->each(
            fn (User $user) =>
            tap(notification()
                ->for($user)
                ->message($grantedMessage)
                ->subject($user)
                ->url(ui_route('quotes.status', ['quote' => $quote]))
                ->priority(2)
                ->push(), fn () => $user->notify(new GrantedQuoteAccess($causer, $quote)))
        );

        $revoked->each(
            fn (User $user) =>
            tap(notification()
                ->for($user)
                ->message($revokedMessage)
                ->subject($user)
                ->priority(2)
                ->push(), fn () => $user->notify(new RevokedQuoteAccess($causer, $quote)))
        );

        return true;
    }
}