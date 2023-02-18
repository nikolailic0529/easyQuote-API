<?php

namespace App\Domain\Attachment\Policies;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attachment $attachment): Response
    {
        if ($attachment->getFlag(Attachment::IS_DELETE_PROTECTED)) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('attachment')
                ->reason('Delete protected')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }
}
