<?php

namespace App\Http\Requests\Stats;

use App\Models\User;
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
     *
     * @return string|null
     */
    public function resolveEntityKeyOfActingUser(): ?string
    {
        /** @var User|null $user */
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
