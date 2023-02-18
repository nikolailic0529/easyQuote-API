<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Worldwide\DataTransferObjects\Opportunity\MarkOpportunityAsLostData;
use Illuminate\Foundation\Http\FormRequest;

class MarkOpportunityAsLostRequest extends FormRequest
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
                'bail', 'required', 'string', 'max:500',
            ],
        ];
    }

    public function getMarkOpportunityAsLostData(): MarkOpportunityAsLostData
    {
        return new MarkOpportunityAsLostData([
            'status_reason' => $this->input('status_reason'),
        ]);
    }
}
