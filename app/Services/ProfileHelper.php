<?php

namespace App\Services;

use App\Models\Template\HpeContractTemplate;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

class ProfileHelper
{
    public static function flushHpeContractTemplateProfiles(HpeContractTemplate $hpeContractTemplate, string $reason = 'deleted')
    {
        DB::transaction(
            fn () => User::query()->where('hpe_contract_template_id', $hpeContractTemplate->getKey())->get('id', 'email', 'first_name', 'middle_name', 'last_name')
                ->each(function (User $user) use ($reason) {
                    notification()
                        ->for($user)
                        ->url(ui_route('users.profile'))
                        ->subject($user)
                        ->message("Hi! Your Profile Default HPE Contract Template has been {$reason}. Please choose another one in the profile.")
                        ->queue();

                    $user->timestamps = false;

                    $user->withoutEvents(fn () => $user->update(['hpe_contract_template_id' => null]));
                }),
            DB_TA
        );
    }

    /**
     * Retrieve Companies Ids from the User Profile.
     *
     * @param  User|null $user
     * @return array
     */
    public static function profileCompaniesIds(?User $user = null): array
    {
        /** @var User $user */
        $user ??= auth()->user();

        if (is_null($user)) {
            return [];
        }

        return $user->companies()->toBase()->select('companies.id')->pluck('id')->all();
    }

    public static function defaultCompanyId(?User $user = null)
    {
        /** @var User $user */
        $user ??= auth()->user();

        return $user?->company_id;
    }
}
