<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Company\Models\Company;
use App\Domain\Currency\Models\Currency;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\QuoteSetupStage;
use App\Domain\Worldwide\Enum\PackQuoteStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ProcessQuoteAddressesContactsRequest extends FormRequest
{
    protected ?QuoteSetupStage $stage = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')->where('type', 'Internal'),
            ],
            'quote_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'quote_template_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(QuoteTemplate::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'quote_expiry_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
            'buy_price' => [
                'bail', 'required', 'numeric', 'min:0', 'max:999999999',
            ],

            'payment_terms' => [
                'bail', 'required', 'string', 'max:500',
            ],

            'are_end_user_addresses_available' => [
                'bail', 'boolean',
            ],

            'are_end_user_contacts_available' => [
                'bail', 'boolean',
            ],

            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels()),
            ],
        ];
    }

    public function getStage(): QuoteSetupStage
    {
        return $this->stage ??= with(true, function () {
            return new QuoteSetupStage([
                'company_id' => $this->input('company_id'),
                'quote_currency_id' => $this->input('quote_currency_id'),
                'quote_template_id' => $this->input('quote_template_id'),
                'quote_expiry_date' => Carbon::createFromFormat('Y-m-d', $this->input('quote_expiry_date')),
                'buy_price' => (float) $this->input('buy_price'),
                'payment_terms' => $this->input('payment_terms'),
                'are_end_user_addresses_available' => $this->boolean('are_end_user_addresses_available'),
                'are_end_user_contacts_available' => $this->boolean('are_end_user_contacts_available'),
                'stage' => PackQuoteStage::getValueOfLabel($this->input('stage')),
            ]);
        });
    }
}
