<?php namespace App\Repositories\QuoteTemplate;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface;
use App\Http\Requests\QuoteTemplate\StoreQuoteTemplateRequest;
use App\Http\Requests\QuoteTemplate\UpdateQuoteTemplateRequest;
use App\Models\QuoteTemplate\QuoteTemplate;
use App\Repositories\SearchableRepository;

class QuoteTemplateRepository extends SearchableRepository implements QuoteTemplateRepositoryInterface
{
    protected $quoteTemplate;

    public function __construct(QuoteTemplate $quoteTemplate)
    {
        parent::__construct();
        $this->quoteTemplate = $quoteTemplate;
    }

    public function userQuery(): Builder
    {
        return $this->quoteTemplate->query()->currentUser()->with('company', 'vendor', 'countries');
    }

    public function all(): Paginator
    {
        $activated = $this->filterQuery($this->userQuery()->activated());
        $deactivated = $this->filterQuery($this->userQuery()->deactivated());

        return $activated->union($deactivated)->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'created_at^3'
        ];

        $items = $this->searchOnElasticsearch($this->quoteTemplate, $searchableFields, $query);

        $activated = $this->buildQuery($this->quoteTemplate, $items, function ($query) {
            return $query->currentUser()->with('company', 'vendor', 'countries')->activated();
        });
        $deactivated = $this->buildQuery($this->quoteTemplate, $items, function ($query) {
            return $query->currentUser()->with('company', 'vendor', 'countries')->deactivated();
        });

        return $activated->union($deactivated)->apiPaginate();
    }

    public function find(string $id): QuoteTemplate
    {
        $quoteTemplate = $this->userQuery()
            ->whereId($id)
            ->with('templateFields.templateFieldType')
            ->firstOrFail();

        $quoteTemplate->templateFields->each(function ($field) {
            $field->type = $field->templateFieldType->name;
            $field->makeHidden('templateFieldType');
        });

        return $quoteTemplate;
    }

    public function findByCompanyVendorCountry(string $companyId, string $vendorId, string $countryId)
    {
        return $this->userQuery()
            ->with('templateFields')
            ->where('quote_templates.company_id', $companyId)
            ->where('quote_templates.vendor_id', $vendorId)
            ->join('country_quote_template', function ($join) use ($countryId) {
                $join->on('quote_templates.id', '=', 'country_quote_template.quote_template_id')
                    ->where('country_id', $countryId);
            })
            ->get();
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

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\QuoteTemplate\OrderByName::class,
            \App\Http\Query\QuoteTemplate\OrderByCompanyName::class,
            \App\Http\Query\QuoteTemplate\OrderByVendorName::class
        ];
    }
}
