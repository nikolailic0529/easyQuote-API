<?php

namespace App\Policies;

use App\Models\Note\Note;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class NotePolicy
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
        return $this->allow();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note\Note  $note
     * @return Response
     */
    public function view(User $user, Note $note): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function create(User $user): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note\Note  $note
     * @return Response
     */
    public function update(User $user, Note $note): Response
    {
        if ($note->getFlag(Note::SYSTEM)) {
            return ResponseBuilder::deny()
                ->item('note')
                ->action('update')
                ->reason("You can't update system generated note")
                ->toResponse();
        }

        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($note->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->item('note')
            ->action('update')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note\Note  $note
     * @return Response
     */
    public function delete(User $user, Note $note): Response
    {
        if ($note->getFlag(Note::SYSTEM)) {
            return ResponseBuilder::deny()
                ->item('note')
                ->action('delete')
                ->reason("You can't delete system generated note")
                ->toResponse();
        }

        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($note->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->item('note')
            ->action('delete')
            ->toResponse();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note\Note  $note
     * @return mixed
     */
    public function restore(User $user, Note $note)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note\Note  $note
     * @return mixed
     */
    public function forceDelete(User $user, Note $note)
    {
        //
    }
}
