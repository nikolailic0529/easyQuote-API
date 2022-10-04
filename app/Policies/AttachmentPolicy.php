<?php

namespace App\Policies;

use App\Enum\AttachmentType;
use App\Models\Attachment;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AttachmentPolicy
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
     * @param  \App\Models\Attachment  $attachment
     * @return Response
     */
    public function view(User $user, Attachment $attachment): Response
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
     * @param  \App\Models\Attachment  $attachment
     * @return Response
     */
    public function update(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attachment  $attachment
     * @return Response
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
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attachment  $attachment
     * @return Response
     */
    public function restore(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attachment  $attachment
     * @return Response
     */
    public function forceDelete(User $user, Attachment $attachment): Response
    {
        return $this->allow();
    }
}
