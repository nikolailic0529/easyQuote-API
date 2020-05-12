<?php

namespace App\Http\Requests\Stats;

use App\Contracts\Repositories\CountryRepositoryInterface as Countries;
use App\Models\Data\Country;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Summary extends FormRequest
{
    protected Countries $countries;

    public function __construct(Countries $countries)
    {
        $this->countries = $countries;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'country_id' => ['nullable', 'uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'start_date' => 'required_with:end_date|string|date_format:Y-m-d',
            'end_date' => 'required_with:start_date|string|date_format:Y-m-d',
        ];
    }

    public function period(): ?CarbonPeriod
    {
        if ($this->has(['start_date', 'end_date'])) {
            return CarbonPeriod::create($this->start_date, $this->end_date);
        }

        return null;
    }
}
