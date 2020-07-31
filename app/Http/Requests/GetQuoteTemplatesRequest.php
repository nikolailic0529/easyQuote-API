<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Contracts\Repositories\QuoteTemplate\{
    QuoteTemplateRepositoryInterface as QuoteTemplates,
    ContractTemplateRepositoryInterface as ContractTemplates
};
use Illuminate\Support\Arr;

class GetQuoteTemplatesRequest extends FormRequest
{
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
            'company_id'        => ['required', 'string', 'uuid', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'vendor_id'         => ['nullable', 'string', 'uuid', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'country_id'        => ['nullable', 'string', 'uuid', Rule::exists('countries', 'id')->whereNull('deleted_at')],
            'quote_template_id' => ['nullable', 'string', 'uuid', Rule::exists('quote_templates', 'id')->whereNull('deleted_at')->where('type', QT_TYPE_QUOTE)],
            'type'              => ['nullable', 'string', Rule::in(QT_TYPES)]
        ];
    }

    public function contract(): bool
    {
        if ($this->missing('type')) {
            return false;
        }

        return Arr::get(array_flip(QT_TYPES), $this->input('type')) === QT_TYPE_CONTRACT;
    }

    public function validated()
    {
        $validated = parent::validated();

        if ($this->missing('quote_template_id')) {
            return $validated;
        }

        $quote_template = app(QuoteTemplates::class)->find($this->quote_template_id);

        return $validated + compact('quote_template');
    }

    public function repository()
    {
        if ($this->contract()) {
            return app(ContractTemplates::class);
        }

        return app(QuoteTemplates::class);
    }
}
