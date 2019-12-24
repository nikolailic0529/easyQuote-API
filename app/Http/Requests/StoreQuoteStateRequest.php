<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models \ {
    Quote\Quote,
    Company,
    Vendor,
    Data\Country,
    Data\Language
};
use Str;

class StoreQuoteStateRequest extends FormRequest
{
    protected $quote;

    protected $company;

    protected $vendor;

    protected $country;

    protected $language;

    protected $types;

    protected $margin;

    public function __construct(
        Quote $quote,
        Company $company,
        Vendor $vendor,
        Country $country,
        Language $language
    ) {
        $this->company = $company;
        $this->vendor = $vendor;
        $this->country = $country;
        $this->language = $language;
        $this->types = collect(__('quote.types'))->implode(',');
        $this->margin['types'] = collect(__('margin.types'))->implode(',');
        $this->margin['methods'] = collect(__('margin.methods'))->implode(',');
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'quote_id' => [
                'uuid',
                'exists:quotes,id'
            ],
            'quote_data.customer_id' => [
                'uuid',
                'exists:customers,id'
            ],
            'quote_data.company_id' => [
                'required_with:quote_data.vendor_id,quote_data.country_id,quote_data.language_id',
                'uuid',
                'exists:companies,id',
            ],
            'quote_data.vendor_id' => [
                'required_with:quote_data.company_id,quote_data.country_id,quote_data.language_id',
                'uuid',
                'exists:vendors,id',
                $this->existsIn('company')
            ],
            'quote_data.country_id' => [
                'required_with:quote_data.company_id,quote_data.vendor_id,quote_data.language_id',
                'uuid',
                'exists:countries,id',
                $this->existsIn('vendor')
            ],
            'quote_data.quote_template_id' => [
                'uuid',
                'exists:quote_templates,id'
            ],
            'quote_data.files.*' => [
                'uuid',
                'exists:quote_files,id'
            ],
            'quote_data.detach_schedule' => [
                'boolean'
            ],
            'quote_data.field_column.*.template_field_id' => [
                'uuid',
                'exists:template_fields,id'
            ],
            'quote_data.field_column.*.importable_column_id' => [
                'present',
                'nullable',
                'uuid',
                'exists:importable_columns,id'
            ],
            'quote_data.field_column.*.is_default_enabled' => [
                'boolean',
            ],
            'quote_data.field_column.*.is_preview_visible' => [
                'boolean',
            ],
            'quote_data.field_column.*.default_value' => [
                'string',
                'min:1'
            ],
            /**
             * Imported Rows
             */
            'quote_data.selected_rows.*' => [
                'uuid',
                'exists:imported_rows,id'
            ],
            'quote_data.selected_rows_is_rejected' => 'boolean',
            'quote_data.use_groups' => [
                'boolean',
                function ($attribute, $value, $fail) {
                    if (blank($this->quote_id) || !$value) {
                        return;
                    }

                    $quote = Quote::whereId($this->quote_id)->firstOrFail();
                    if ($quote->has_not_group_description) {
                        $fail('For using the Grouped Rows assign at least one group.');
                    }
                }
            ],
            'quote_data.last_drafted_step' => 'string|max:20',
            'quote_data.pricing_document' => 'string|max:40|min:2',
            'quote_data.service_agreement_id' => 'string|max:40|min:2',
            'quote_data.system_handle' => 'string|max:40|min:2',
            'quote_data.additional_details' => 'nullable|string|max:20000|min:2',
            'quote_data.checkbox_status' => 'array',
            'quote_data.closing_date' => 'date_format:Y-m-d',
            'quote_data.additional_notes' => 'string|max:20000|min:2',
            'quote_data.calculate_list_price' => 'boolean',
            'quote_data.buy_price' => 'nullable|numeric|min:0',
            'quote_data.custom_discount' => 'nullable|numeric|min:0',

            /**
             * Hide Fields in Review Quote Screen.
             */
            'quote_data.hidden_fields' => 'array',
            'quote_data.hidden_fields.*' => 'string|in:searchable,description,qty',

            /**
             * Sort Fields by ascending/descending.
             */
            'quote_data.sort_fields' => 'array',
            'quote_data.sort_fields.*.name' => 'required|string|in:product_no,description,serial_no,date_from,date_to,qty,price,searchable',
            'quote_data.sort_fields.*.direction' => 'required|nullable|string|in:asc,desc',

            /**
             * Sort Rows Groups by ascending/descending.
             */
            'quote_data.sort_group_description' => 'array',
            'quote_data.sort_group_description.*.name' => 'required|string|in:name,search_text,total_count,total_price',
            'quote_data.sort_group_description.*.direction' => 'required|nullable|string|in:asc,desc',

            /**
             * Margin
             */
            'margin.quote_type' => [
                'string',
                'required_with:margin.is_fixed,margin.method,margin.type,margin.value',
                'in:' . $this->types
            ],
            'margin.method' => [
                'required_with:margin.is_fixed,margin.type,margin.quote_type,margin.value',
                'in:' . $this->margin['methods']
            ],
            'margin.is_fixed' => 'required_with:margin.method,margin.type,margin.quote_type,margin.value|boolean',
            'margin.value' => [
                'required_with:margin.method,margin.type,margin.quote_type,margin.is_fixed',
                'numeric',
                $this->ifEquals('margin.is_fixed', false, 'max:100')
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
             * Pass true for submit a quote
             */
            'save' => 'boolean'
        ];
    }

    public function ifEquals($anotherAttribute, $value, $rule)
    {
        if($this->input($anotherAttribute) == $value) {
            return $rule;
        }
    }

    public function existsIn($parentName)
    {
        return function ($attribute, $value, $fail) use ($parentName) {
            $parentKey = $this->getKey($parentName);
            $parentId = $this->input($parentKey);

            $childName = Str::before(Str::after($attribute, 'quote_data.'), '_id');
            $childId = $value;

            $exists = $this->{$parentName}->whereId($parentId)
                ->whereHas(Str::pluralStudly($childName), function ($query) use ($childId) {
                    return $query->whereId($childId);
                })->exists();

            if(!$exists) {
                $parentName = Str::title($parentName);
                $childName = Str::title($childName);

                $fail("The {$parentName} must have the {$childName}");
            }
        };
    }

    public function messages()
    {
        return [
            'margin.value.max' => 'Margin Percentage can not be greater :max%',
            'quote_data.service_agreement_id.string' => 'Service Agreement ID must not be empty.'
        ];
    }

    public function validatedData()
    {
        return collect($this->validated());
    }

    public function validatedQuoteData()
    {
        return $this->validatedData()->get('quote_data');
    }

    public function quote()
    {
        return $this->has('quote_id') ? Quote::whereId($this->quote_id)->first() : $this->user()->quotes()->make();
    }

    protected function getKey(String $model)
    {
        return Str::finish(Str::start($model, 'quote_data.'), '_id');
    }
}
