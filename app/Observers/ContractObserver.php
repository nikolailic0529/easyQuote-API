<?php

namespace App\Observers;

use App\Models\Quote\Contract;
use App\Models\User;

class ContractObserver
{
    /**
     * Handle the contract "created" event.
     *
     * @param  \App\App\Models\Quote\Contract  $contract
     * @return void
     */
    public function submitted(Contract $contract)
    {
        $contract_number = $contract->contract_number;
        $causer = request()->user();
        $url = ui_route('contracts.submitted.review', compact('contract'));
        $notificationRecepients = collect([$contract->user, $contract->quote->user])
            ->whereInstanceOf(User::class)
            ->unique('id');

        slack()
            ->title('Contract Submission')
            ->url($url)
            ->status([CTSS_01, 'Contract Number' => $contract_number, 'Caused By' => $causer->fullname])
            ->image(assetExternal(SN_IMG_QSS))
            ->send();

        $notificationRecepients->each(function ($user) use ($url, $contract, $contract_number) {
            notification()
                ->for($user)
                ->message(__(CTSS_02, compact('contract_number')))
                ->subject($contract)
                ->url($url)
                ->priority(1)
                ->queue();
        });
    }


    /**
     * Handle the Contract "deleted" event.
     *
     * @param \App\App\Models\Quote\Contract $contract
     * @return void
     */
    public function deleted(Contract $contract)
    {
        $contract_number = $contract->contract_number;

        notification()
            ->for($contract->user)
            ->message(__(CTD_01, compact('contract_number')))
            ->subject($contract)
            ->url(ui_route('users.notifications'))
            ->priority(3)
            ->queue();
    }
}
