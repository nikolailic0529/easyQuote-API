<?php

namespace App\Domain\Stats\Requests;

use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rule;

class SummaryRequest extends StatsAggregatorRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'country_id' => ['nullable', 'uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'currency_id' => ['nullable', 'uuid', Rule::exists(Currency::class, 'id')],
            'start_date' => 'required_with:end_date|string|date_format:Y-m-d',
            'end_date' => 'required_with:start_date|string|date_format:Y-m-d',
        ];
    }

    public function resolvePeriod(): ?CarbonPeriod
    {
        if ($this->has(['start_date', 'end_date'])) {
            return CarbonPeriod::createFromArray([
                Carbon::createFromFormat('Y-m-d', $this->input('start_date')),
                Carbon::createFromFormat('Y-m-d', $this->input('end_date')),
            ]);
        }

        return null;
    }

    public function getCountryEntityKey(): ?string
    {
        return $this->input('country_id');
    }

    public function getCurrencyEntityKey(): ?string
    {
        return $this->input('currency_id');
    }

    public function getSummaryRequestData(): \App\Domain\Stats\DataTransferObjects\SummaryRequestData
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = $this->user();

        $gate = $this->container[Gate::class];

        $classMap = array_flip(Relation::$morphMap);

        $quoteEntityTypes = [
            $classMap[Quote::class] => $gate->allows('viewAny', Quote::class),
            $classMap[WorldwideQuote::class] => $gate->allows('viewAny', WorldwideQuote::class),
        ];

        $allowedQuoteEntityTypes = array_filter($quoteEntityTypes);

        return new \App\Domain\Stats\DataTransferObjects\SummaryRequestData([
            'period' => $this->resolvePeriod(),
            'country_id' => $this->input('country_id'),
            'currency_id' => $this->input('currency_id'),
            'acting_user_id' => $user->getKey(),
            'acting_user_team_id' => $user->team()->getParentKey(),
            'acting_user_led_teams' => $user->ledTeams()->pluck('id')->all(),
            'any_owner_entities' => $gate->allows('viewQuotesOfAnyUser'),
            'entity_types' => array_keys($allowedQuoteEntityTypes),
        ]);
    }
}
