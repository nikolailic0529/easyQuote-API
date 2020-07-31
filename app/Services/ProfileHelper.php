<?php

namespace App\Services;

use App\Models\QuoteTemplate\HpeContractTemplate;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

class ProfileHelper
{
    public static function flushRoleUserProfiles(Role $role)
    {
        $roleAllowedCompanies = $role->companies()->getQuery()->pluck('id');

        DB::transaction(
            fn () => $role->users()->whereNotIn('company_id', $roleAllowedCompanies)->update(['company_id' => null, 'hpe_contract_template_id' => null]),
            DB_TA
        );

        $role->users->whereNotIn('company_id', $roleAllowedCompanies)
            ->each(
                fn (User $user) =>
                notification()
                    ->for($user)
                    ->url(ui_route('users.profile'))
                    ->subject($user)
                    ->message('Hi! Your role allowed companies have been recently changed. Please choose another one in the profile.')
                    ->queue()
            );
    }

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

    public static function listenAndFlushUserProfile(User $user, Closure $closure)
    {
        /** @var \App\Models\Role */
        $initialRole = $user->roles()->getQuery()->first();

        return tap(call_user_func($closure), function () use ($user, $initialRole) {
            /** @var \App\Models\Role */
            $actualRole = $user->roles()->getQuery()->first();

            if ($actualRole->is($initialRole)) {
                return;
            }

            /** @var \Illuminate\Support\Collection */
            $actualRoleCompanies = $actualRole->companies()->pluck('id');

            if ($actualRoleCompanies->contains($user->company_id)) {
                return;
            }

            DB::transaction(function () use ($user) {
                $user->timestamps = false;

                $user->withoutEvents(fn () => $user->update(['company_id' => null, 'hpe_contract_template_id' => null]));
            }, DB_TA);

            notification()
                ->for($user)
                ->url(ui_route('users.profile'))
                ->subject($user)
                ->message("Hi! Your Role has been recently changed. Please choose another Default Company and HPE Contract Template.")
                ->queue();
        });
    }

    /**
     * Retrieve Companies Ids from the User Profile.
     *
     * @param  User|null $user
     * @return void
     */
    public static function profileCompaniesIds(?User $user = null)
    {
        /** @var User */
        $user ??= auth()->user();

        if ($user === null) {
            return [];       
        }

        return $user->companies()->toBase()->select('companies.id')->pluck('id')->toArray();
    }
}
