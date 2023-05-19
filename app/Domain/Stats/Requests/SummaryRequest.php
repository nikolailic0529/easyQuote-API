<?php

namespace App\Domain\Stats\Requests;

use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Stats\DataTransferObjects\SummaryRequestData;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\Rule;

final class SummaryRequest extends StatsAggregatorRequest
{
    public function rules(): array
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

    public function getSummaryRequestData(): SummaryRequestData
    {
        /** @var User $user */
        $user = $this->user();

        return new SummaryRequestData(
            userId: $user->getKey(),
            period: $this->resolvePeriod(),
            countryId: $this->input('country_id'),
            currencyId: $this->input('currency_id'),
        );
    }
}
