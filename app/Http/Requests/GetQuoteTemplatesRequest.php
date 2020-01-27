<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Contracts\Repositories\QuoteTemplate\{
    QuoteTemplateRepositoryInterface,
    ContractTemplateRepositoryInterface
};

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
            'company_id' => 'required|exists:companies,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'country_id' => 'nullable|exists:countries,id',
            'type' => ['nullable', 'string', Rule::in(QT_TYPES)]
        ];
    }

    public function contract(): bool
    {
        return $this->input('type') === QT_TYPE_CONTRACT;
    }

    public function repository()
    {
        if ($this->contract()) {
            return app(ContractTemplateRepositoryInterface::class);
        }

        return app(QuoteTemplateRepositoryInterface::class);
    }
}
