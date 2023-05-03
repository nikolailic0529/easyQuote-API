<?php

namespace App\Domain\Rescue\Observers;

use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Notifications\ContractDeletedNotification;
use App\Domain\Rescue\Notifications\ContractSubmittedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class ContractObserver
{
    public function submitted(Contract $contract): void
    {
        $notifiables = Collection::make([$contract->user, $contract->quote->user])
            ->filter()
            ->unique()
            ->values();

        Notification::send($notifiables, new ContractSubmittedNotification($contract));
    }

    public function deleted(Contract $contract): void
    {
        if ($contract->user) {
            $contract->user->notify(new ContractDeletedNotification($contract));
        }
    }
}
