<?php

namespace App\Http\Requests\Opportunity;

use App\DTO\Opportunity\MarkOpportunityAsLostData;
use Illuminate\Foundation\Http\FormRequest;

class MarkOpportunityAsLost extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status_reason' => [
                'bail', 'required', 'string', 'max:500'
            ]
        ];
    }

    public function getMarkOpportunityAsLostData(): MarkOpportunityAsLostData
    {
        return new MarkOpportunityAsLostData([
            'status_reason' => $this->input('status_reason')
        ]);
    }
}
