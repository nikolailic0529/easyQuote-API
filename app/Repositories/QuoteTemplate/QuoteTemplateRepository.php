<?php

namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Models\QuoteTemplate\QuoteTemplate;
use App\Http\Requests\QuoteTemplate\{
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use Illuminate\Support\Collection as SupportCollection;

class QuoteTemplateRepository extends SearchableRepository implements QuoteTemplateRepositoryInterface
{
    protected $quoteTemplate;

    protected $selectable = ['id', 'name', 'company_id', 'vendor_id', 'is_system', 'activated_at'];

    public function __construct(QuoteTemplate $quoteTemplate)
    {
        $this->quoteTemplate = $quoteTemplate;
    }

    public function userQuery(): Builder
    {
        return $this->quoteTemplate->query()->with('company:id,name', 'vendor:id,name', 'countries:id,name');
    }

    public function find(string $id): QuoteTemplate
    {
        $quoteTemplate = $this->userQuery()
            ->whereId($id)
            ->with('templateFields.templateFieldType')
            ->firstOrFail()
            ->makeVisible(['form_data', 'form_values_data']);

        $quoteTemplate->templateFields->each(function ($field) {
            $field->type = $field->templateFieldType->name;
            $field->makeHidden('templateFieldType');
        });

        return $quoteTemplate;
    }

    public function findByCompanyVendorCountry(string $companyId, string $vendorId, string $countryId)
    {
        return $this->userQuery()
            ->where('quote_templates.company_id', $companyId)
            ->where('quote_templates.vendor_id', $vendorId)
            ->join('country_quote_template', function ($join) use ($countryId) {
                $join->on('quote_templates.id', '=', 'country_quote_template.quote_template_id')
                    ->where('country_id', $countryId);
            })
            ->get(['id', 'name'])
            ->each(function ($template) {
                $template->makeHiddenExcept(['id', 'name']);
            });
    }

    public function designer(string $id): SupportCollection
    {
        $template = $this->find($id)->load('company', 'vendor.image');

        $company_logos = $template->company->appendLogo()->logoDimensions;
        $vendor_logos = $template->vendor->appendLogo()->logoDimensions;

        $designer = collect(__('template.designer'))->transform(function ($page) {
            return collect($page)->transform(function ($tag) {
                return array_merge($tag, ['is_image' => false]);
            })->toArray();
        });

        $designer['first_page'] = array_merge($designer['first_page'], $company_logos, $vendor_logos);

        return $designer;
    }

    public function create(StoreQuoteTemplateRequest $request): QuoteTemplate
    {
        $quoteTemplate = request()->user()->quoteTemplates()->create($request->validated());
        $quoteTemplate->syncCountries($request->countries);

        return $quoteTemplate->load('company', 'vendor', 'countries', 'templateFields');
    }

    public function update(UpdateQuoteTemplateRequest $request, string $id): QuoteTemplate
    {
        $quoteTemplate = $this->find($id);
        $quoteTemplate->update($request->validated());
        $quoteTemplate->syncCountries($request->countries);

        return $quoteTemplate->load('company', 'vendor', 'countries', 'templateFields');
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    public function copy(string $id): bool
    {
        $replicatableTemplate = $this->find($id);
        $template = $replicatableTemplate->replicate(['user', 'countries', 'templateFields']);
        $countries = $replicatableTemplate->countries->pluck('id')->toArray();
        $templateFields = $replicatableTemplate->templateFields->pluck('id')->toArray();

        return $template->save() && $template->syncCountries($countries) && $template->syncTemplateFields($templateFields);
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\QuoteTemplate\OrderByCompanyName::class,
            \App\Http\Query\QuoteTemplate\OrderByVendorName::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->select($this->selectable)->activated(),
            $this->userQuery()->select($this->selectable)->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->quoteTemplate;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'countries.name^4', 'vendor.name^4', 'company.name^4', 'created_at^3'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query->with('company:id,name', 'vendor:id,name', 'countries:id,name')->select($this->selectable);
    }
}
