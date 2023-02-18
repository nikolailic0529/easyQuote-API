<?php

namespace App\Domain\UnifiedQuote\Requests;

use App\Domain\Rescue\Models\Quote;
use App\Domain\UnifiedQuote\DataTransferObjects\UnifiedQuotesRequestData;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideQuote;
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
