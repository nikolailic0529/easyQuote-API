<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class ShowOpportunityRequest extends FormRequest
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

    public function loadOpportunity(Opportunity $opportunity): Opportunity
    {
        $opportunity->loadExists('worldwideQuotes as quotes_exist');

        return $opportunity;
    }
}
