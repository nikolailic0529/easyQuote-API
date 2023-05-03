<?php

namespace App\Domain\Template\Requests\HpeContractTemplate;

use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Template\Contracts\HpeContractTemplate as HpeContractTemplates;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterHpeTemplatesRequest extends FormRequest
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
            'company_id' => ['nullable', 'string', 'uuid', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'country_id' => ['nullable', 'string', 'uuid', Rule::exists('countries', 'id')->whereNull('deleted_at')],
        ];
    }

    public function getFilteredTemplates(): Collection
    {
        if ($this->missing(['company_id', 'country_id'])) {
            return Collection::make();
        }

        /** @var \App\Domain\User\Models\User */
        $user = optional(auth()->user());

        return $this->templates->findBy($this->validated(), true, ['id', 'name'])
            ->sortByDesc(fn (HpeContractTemplate $template) => $template->getKey() === $user->hpe_contract_template_id)
            ->values();
    }
}
