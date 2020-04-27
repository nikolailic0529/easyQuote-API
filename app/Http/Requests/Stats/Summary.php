<?php

namespace App\Http\Requests\Stats;

use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;

class Summary extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
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
