<?php namespace App\Policies;

use App\Models \ {
    User,
    QuoteTemplate\TemplateField
};
use Illuminate\Auth\Access\HandlesAuthorization;

class TemplateFieldPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any template fields.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the template field.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\TemplateField  $templateField
     * @return mixed
     */
    public function view(User $user, TemplateField $templateField)
    {
        //
    }

    /**
     * Determine whether the user can create template fields.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the template field.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\TemplateField  $templateField
     * @return mixed
     */
    public function update(User $user, TemplateField $templateField)
    {
        //
    }

    /**
     * Determine whether the user can delete the template field.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\TemplateField  $templateField
     * @return mixed
     */
    public function delete(User $user, TemplateField $templateField)
    {
        //
    }

    /**
     * Determine whether the user can restore the template field.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\TemplateField  $templateField
     * @return mixed
     */
    public function restore(User $user, TemplateField $templateField)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the template field.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\TemplateField  $templateField
     * @return mixed
     */
    public function forceDelete(User $user, TemplateField $templateField)
    {
        //
    }
}
