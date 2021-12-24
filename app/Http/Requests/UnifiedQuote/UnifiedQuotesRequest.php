<?php

namespace App\Http\Requests\UnifiedQuote;

use App\DTO\UnifiedQuote\UnifiedQuotesRequestData;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UnifiedQuotesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function getUnifiedQuotesRequestData(): UnifiedQuotesRequestData
    {
        /** @var User $user */
        $user = $this->user();

        $gate = $this->container[Gate::class];

        return new UnifiedQuotesRequestData([
            'acting_user_id' => $user->getKey(),
            'acting_user_team_id' => $user->team()->getParentKey(),
            'acting_user_led_teams' => $user->ledTeams()->pluck('id')->all(),
            'get_rescue_entities' => $gate->allows('viewAny', Quote::class),
            'get_worldwide_entities' => $gate->allows('viewAny', WorldwideQuote::class),
            'get_any_owner_entities' => $gate->allows('viewQuotesOfAnyUser'),
        ]);
    }
}
