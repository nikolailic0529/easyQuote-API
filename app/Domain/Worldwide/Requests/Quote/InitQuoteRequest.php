<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\QuoteStages\InitStage;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class InitQuoteRequest extends FormRequest
{
    protected ?InitStage $initStage = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'opportunity_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Opportunity::class, 'id')->whereNull('deleted_at'),
                Rule::unique(WorldwideQuote::class, 'opportunity_id')->whereNull('deleted_at'),
            ],
            'contract_type' => [
                'bail', 'required', 'string', 'in:contract,pack',
            ],
        ];
    }

    public function getStage(): InitStage
    {
        return $this->initStage ??= new InitStage([
            'opportunity_id' => $this->input('opportunity_id'),
            'contract_type_id' => with($this->input('contract_type'), function (string $contractType) {
                return [
                    'contract' => CT_CONTRACT,
                    'pack' => CT_PACK,
                ][$contractType];
            }),
            'user_id' => $this->user()->getKey(),
            'quote_expiry_date' => Carbon::now()->add(CarbonInterval::days(30))->startOfDay(),
        ]);
    }
}
