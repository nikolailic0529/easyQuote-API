<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException, Str;
use App\Models \ {
    Company,
    Vendor,
    Data\Country,
    Data\Language
};

class StoreQuoteStateRequest extends FormRequest
{
    protected $company;

    protected $vendor;

    protected $country;

    protected $language;

    protected $types;

    public function __construct(
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
            'quote_data.field_column.*.template_field_id' => [
                'uuid',
                'exists:template_fields,id'
            ],
            'quote_data.field_column.*.importable_column_id' => [
                'nullable',
                'uuid',
                'exists:importable_columns,id'
            ],
            'quote_data.selected_rows.*' => [
                'uuid',
                'exists:imported_rows,id'
            ],
            'quote_data.selected_rows_is_rejected' => 'boolean',
            'margin.quote_type' => [
                'string',
                'required_with:margin',
                'in:' . $this->types
            ],
            'margin.value' => 'required_with:margin|numeric',
            'margin.is_fixed' => 'required_with:margin|boolean',
            'save' => 'boolean'
        ];
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

    protected function getKey(String $model)
    {
        return Str::finish(Str::start($model, 'quote_data.'), '_id');
    }
}
