<?php

namespace App\Http\Requests\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\HpeContractTemplate as HpeContractTemplates;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterHpeTemplates extends FormRequest
{
    protected HpeContractTemplates $templates;

    public function __construct(HpeContractTemplates $templates)
    {
        $this->templates = $templates;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id'        => ['nullable', 'string', 'uuid', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'country_id'        => ['nullable', 'string', 'uuid', Rule::exists('countries', 'id')->whereNull('deleted_at')],
        ];
    }

    public function getFilteredTemplates(): Collection
    {
        if ($this->missing(['company_id', 'country_id'])) {
            return Collection::make();
        }

        return $this->templates->findBy($this->validated(), ['id', 'name']);
    }
}
