<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use App\Models\{
    Company,
    Vendor,
    Quote\Quote,
    Customer\Customer,
    // Customer\EqCustomer,
    Data\Country,
};
use App\Models\Quote\BaseQuote;
use App\Models\Quote\Margin\Margin;
use App\Rules\{
    UniqueCustomer,
    UseRowGroups,
};

class StoreQuoteStateRequest extends FormRequest
{
    use HandlesAuthorization;

    protected ?Quote $quote = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->customerUpdating()) {
            abort(403, $this->deny(QUC_01));
        }

        if ($this->submitting() && $this->submittedRfqExists()) {
            $this->reportFailedSubmission();

            abort(403, $this->deny(QSE_01));
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_id' => ['uuid', Rule::exists(Quote::class, 'id')->where('is_version', false)->whereNull('deleted_at')],

            'quote_data.customer_id' => [
                $this->customerRequired(),
                'uuid',
                Rule::exists('customers', 'id')->whereNull('deleted_at'),
                (new UniqueCustomer)->ignore($this->quote())
            ],

            'quote_data.company_id' => [
                'required_with:quote_data.vendor_id,quote_data.country_id,quote_data.language_id',
                'uuid',
                Rule::exists(Company::class, 'id')->where('type', 'Internal')->whereNull('deleted_at'),
            ],

            'quote_data.vendor_id' => [
                'required_with:quote_data.company_id,quote_data.country_id,quote_data.language_id',
                'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at'),
                Rule::exists('company_vendor', 'vendor_id')->where('company_id', $this->input('quote_data.company_id'))
            ],

            'quote_data.country_id' => [
                'required_with:quote_data.company_id,quote_data.vendor_id,quote_data.language_id',
                'uuid',
                Rule::exists(Country::class, 'id')->whereNull('deleted_at'),
                Rule::exists('country_vendor', 'country_id')->where('vendor_id', $this->input('quote_data.vendor_id'))
            ],

            'quote_data.quote_template_id' => [
                'uuid',
                Rule::exists('quote_templates', 'id')->whereNull('deleted_at')->where('type', QT_TYPE_QUOTE)
            ],

            'quote_data.contract_template_id' => [
                'uuid',
                Rule::exists('quote_templates', 'id')->whereNull('deleted_at')->where('type', QT_TYPE_CONTRACT)
            ],

            'quote_data.source_currency_id' => ['nullable', 'string', 'uuid', Rule::exists('currencies', 'id')],
            'quote_data.target_currency_id' => ['nullable', 'string', 'uuid', Rule::exists('currencies', 'id')],

            'quote_data.files.*' => ['uuid', 'exists:quote_files,id'],

            'quote_data.detach_schedule' => ['boolean'],

            'quote_data.field_column.*.template_field_id' => [
                'required_with:quote_data.field_column.*.importable_column_id',
                'uuid',
                'exists:template_fields,id'
            ],
            'quote_data.field_column.*.importable_column_id' => ['present', 'nullable', 'uuid', 'exists:importable_columns,id'],
            'quote_data.field_column.*.is_default_enabled' => ['boolean'],
            'quote_data.field_column.*.is_preview_visible' => ['boolean'],
            'quote_data.field_column.*.default_value' => ['string', 'min:1'],

            /**
             * Imported Rows
             */
            'quote_data.selected_rows.*'            => ['uuid', 'exists:imported_rows,id'],
            'quote_data.selected_rows_is_rejected'  => 'boolean',
            'quote_data.use_groups'                 => ['boolean', new UseRowGroups($this->quote())],
            'quote_data.last_drafted_step'          => 'string|max:20',
            'quote_data.pricing_document'           => 'string|max:1000|min:2',
            'quote_data.service_agreement_id'       => 'string|max:1000|min:2',
            'quote_data.system_handle'              => 'string|max:1000|min:2',
            'quote_data.additional_details'         => 'nullable|string|max:20000|min:2',
            'quote_data.checkbox_status'            => 'array',
            'quote_data.closing_date'               => 'date_format:Y-m-d',
            'quote_data.additional_notes'           => 'string|max:20000|min:2',
            'quote_data.calculate_list_price'       => 'boolean',
            'quote_data.buy_price'                  => 'nullable|numeric|min:0',
            'quote_data.exchange_rate_margin'       => 'nullable|numeric|min:0',
            'quote_data.custom_discount'            => 'nullable|numeric|min:0',

            /**
             * Hide Fields in Review Quote Screen.
             */
            'quote_data.hidden_fields'      => 'array',
            'quote_data.hidden_fields.*'    => 'string|in:searchable,description,qty',

            /**
             * Sort Fields by ascending/descending.
             */
            'quote_data.sort_fields'                => 'array',
            'quote_data.sort_fields.*.name'         => 'required|string|in:product_no,description,serial_no,date_from,date_to,qty,price,searchable',
            'quote_data.sort_fields.*.direction'    => 'required|nullable|string|in:asc,desc',

            /**
             * Sort Rows Groups by ascending/descending.
             */
            'quote_data.sort_group_description'             => 'array',
            'quote_data.sort_group_description.*.name'      => 'required|string|in:name,search_text,total_count,total_price',
            'quote_data.sort_group_description.*.direction' => 'required|nullable|string|in:asc,desc',

            /**
             * Margin
             */
            'margin.quote_type' => [
                'string',
                'required_with:margin.is_fixed,margin.method,margin.type,margin.value',
                Rule::in(BaseQuote::TYPES)
            ],
            'margin.method' => [
                'required_with:margin.is_fixed,margin.type,margin.quote_type,margin.value',
                Rule::in(Margin::METHODS)
            ],
            'margin.is_fixed' => 'required_with:margin.method,margin.type,margin.quote_type,margin.value|boolean',
            'margin.value' => [
                'required_with:margin.method,margin.type,margin.quote_type,margin.is_fixed',
                'numeric',
                'min:0'
            ],
            'margin.delete' => [
                'boolean'
            ],

            /**
             * Defined Discounts
             */
            'discounts' => [
                'array'
            ],
            'discounts.*' => [
                'array'
            ],
            'discounts.*.id' => [
                'required',
                'uuid',
                'exists:discounts,discountable_id'
            ],
            'discounts.*.duration' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'discounts_detach' => [
                'boolean'
            ],

            /**
             * Pass true to submit the quote.
             */
            'save' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'margin.value.max'                          => 'Margin Percentage can not be greater than :max%.',
            'quote_data.service_agreement_id.string'    => 'Service Agreement ID must not be empty.',
            'margin.value.numeric'                      => 'Margin Value must be a number.',
            'quote_data.customer_id.required'           => 'Customer is required when creating a new Quote.'
        ];
    }

    public function validatedData(): Collection
    {
        return collect($this->validated());
    }

    public function validatedQuoteData(): array
    {
        return $this->validatedData()->get('quote_data') ?? [];
    }

    public function quote(): Quote
    {
        if (isset($this->quote)) {
            return $this->quote;
        }

        return $this->quote = $this->has('quote_id')
            ? Quote::findOrFail($this->quote_id)
            : $this->user()->quotes()->make();
    }

    public function quoteRfq(): string
    {
        return $this->quote->rfq_number ??
            Customer::whereKey($this->input('quote_data.customer_id'))->value('rfq');
    }

    protected function customerUpdating(): bool
    {
        return $this->quote()->exists &&
            $this->has('quote_data.customer_id') &&
            $this->input('quote_data.customer_id') !== $this->quote->customer_id;
    }

    protected function customerRequired()
    {
        return is_null($this->quote()->customer_id)
            && $this->missing('quote_data.customer_id')
            ? 'required' : null;
    }

    protected function submitting(): bool
    {
        return (bool) $this->input('save');
    }

    protected function submittedRfqExists(): bool
    {
        return Quote::query()
            ->whereKeyNot($this->quote()->id)
            ->submitted()
            ->activated()
            ->rfq($this->quoteRfq())
            ->exists();
    }

    protected function reportFailedSubmission(): void
    {
        slack()
            ->title('Quote Submission')
            ->url(ui_route('quotes.drafted.review', ['quote' => $this->quote()]))
            ->status([QSF_01, 'Quote RFQ' => $this->quoteRfq(), 'Reason' => QSE_01, 'Caused By' => optional($this->user())->fullname])
            ->image(assetExternal(SN_IMG_QSF))
            ->queue();
    }
}
