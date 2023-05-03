<?php

namespace App\Domain\Stats\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsAggregatorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Resolve user id when user does not have super role.
     */
    public function resolveEntityKeyOfActingUser(): ?string
    {
        /** @var \App\Domain\User\Models\User|null $user */
        $user = $this->user();

        if (is_null($user)) {
            return null;
        }

        if ($user->hasRole(R_SUPER)) {
            return null;
        }

        return $user->getKey();
    }
}
