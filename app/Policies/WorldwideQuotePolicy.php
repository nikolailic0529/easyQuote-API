<?php

namespace App\Policies;

use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WorldwideQuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_own_ww_quotes')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models of any owner.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function viewAnyOwnerEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuote  $worldwideQuote
     * @return Response
     */
    public function view(User $user, WorldwideQuote $worldwideQuote): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_own_ww_quotes')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('sales order')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($worldwideQuote->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($worldwideQuote->salesUnit)) {
            if ($worldwideQuote->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('view')
                ->item('worldwide quote')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('worldwide quote')
            ->toResponse();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_ww_quotes')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuote  $worldwideQuote
     * @return Response
     */
    public function update(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = $this->getBaseUpdateResponse($user, $worldwideQuote);

        if ($response->allowed()) {
            if (null === $worldwideQuote->submitted_at) {
                return $response;
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->reason('Quote is submitted')
                ->toResponse();
        }

        return $response;
    }

    /**
     * Determine whether the user can delete the specified version of the model.
     *
     * @param  User  $user
     * @param  WorldwideQuote  $worldwideQuote
     * @param  WorldwideQuoteVersion  $version
     * @return Response
     */
    public function deleteVersion(User $user, WorldwideQuote $worldwideQuote, WorldwideQuoteVersion $version): Response
    {
        $response = $this->getBaseUpdateResponse($user, $worldwideQuote);

        if ($response->allowed()) {
            if (null === $worldwideQuote->submitted_at) {
                return $response;
            }

            if ($worldwideQuote->activeVersion()->is($version)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote version')
                    ->reason('The version is active')
                    ->toResponse();
            }

            return ResponseBuilder::deny()
                ->action('delete')
                ->item('worldwide quote version')
                ->reason('Quote is submitted')
                ->toResponse();
        }

        return $response;
    }

    /**
     * Determine whether the user can change status of the model.
     *
     * @param  User  $user
     * @param  WorldwideQuote  $worldwideQuote
     * @return Response
     */
    public function changeStatus(User $user, WorldwideQuote $worldwideQuote): Response
    {
        return $this->getBaseUpdateResponse($user, $worldwideQuote);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuote  $worldwideQuote
     * @return Response
     */
    public function delete(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = (function () use ($user, $worldwideQuote): Response {
            if ($user->hasRole(R_SUPER)) {
                return $this->allow();
            }

            if ($user->cant('delete_own_ww_quotes')) {
                return ResponseBuilder::deny()
                    ->action('update')
                    ->item('worldwide quote')
                    ->toResponse();
            }

            if ($user->salesUnitsFromLedTeams->contains($worldwideQuote->salesUnit)) {
                return $this->allow();
            }

            if ($user->salesUnits->contains($worldwideQuote->salesUnit)) {
                if ($worldwideQuote->user()->is($user)) {
                    return $this->allow();
                }

                return ResponseBuilder::deny()
                    ->action('update')
                    ->item('worldwide quote')
                    ->reason('You must be an owner')
                    ->toResponse();
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->toResponse();
        })();

        if ($response->allowed()) {
            if ($this->salesOrderExists($worldwideQuote)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote')
                    ->reason('Sales order exists')
                    ->toResponse();
            }

            return $response;
        }

        return $response;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuote  $worldwideQuote
     * @return Response
     */
    public function export(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = $this->view($user, $worldwideQuote);

        if ($response->allowed()) {
            if (null === $worldwideQuote->submitted_at) {
                return ResponseBuilder::deny()
                    ->action('export')
                    ->item('worldwide quote')
                    ->reason('Quote must be submitted')
                    ->toResponse();
            }
        }

        return $response;
    }

    /**
     * Determine whether the user can unravel the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuote  $worldwideQuote
     * @return Response
     */
    public function unravel(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = $this->getBaseUpdateResponse($user, $worldwideQuote);

        if ($response->allowed()) {
            if ($this->salesOrderExists($worldwideQuote)) {
                return ResponseBuilder::deny()
                    ->action('unravel')
                    ->item('worldwide quote')
                    ->reason('Sales order exists')
                    ->toResponse();
            }
        }

        return $response;
    }

    /**
     * Determine whether the user can replicate the model.
     *
     * @param  User  $user
     * @param  WorldwideQuote  $worldwideQuote
     * @return mixed
     */
    public function replicate(User $user, WorldwideQuote $worldwideQuote): Response
    {
        return $this->create($user);
    }

    protected function salesOrderExists(WorldwideQuote $worldwideQuote): bool
    {
        // When explicitly defined sales_order_exists field is present on the model entity,
        // We will use it to check an existence of sales order.
        // This is done for optimization of listing queries.
        if (isset($worldwideQuote->sales_order_exists)) {
            return (bool) $worldwideQuote->sales_order_exists;
        } else {
            return $worldwideQuote->salesOrder()->exists();
        }
    }

    /**
     * @param  User  $user
     * @param  WorldwideQuote  $worldwideQuote
     * @return Response
     */
    protected function getBaseUpdateResponse(User $user, WorldwideQuote $worldwideQuote): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_ww_quotes')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($worldwideQuote->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($worldwideQuote->salesUnit)) {
            if ($worldwideQuote->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('worldwide quote')
            ->toResponse();
    }
}
