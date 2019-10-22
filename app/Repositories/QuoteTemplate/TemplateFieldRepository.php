<?php namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface;
use App\Builder\Pagination\Paginator;
use App\Http\Requests\QuoteTemplate \ {
    StoreTemplateFieldRequest,
    UpdateTemplateFieldRequest
};
use App\Models\QuoteTemplate\TemplateField;
use App\Models\QuoteTemplate\TemplateFieldType;
use App\Repositories\SearchableRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TemplateFieldRepository extends SearchableRepository implements TemplateFieldRepositoryInterface
{
    protected $templateField;

    protected $templateFieldType;

    public function __construct(TemplateField $templateField, TemplateFieldType $templateFieldType)
    {
        $this->templateField = $templateField;
        $this->templateFieldType = $templateFieldType;
    }

    public function userQuery(): Builder
    {
        return $this->templateField->query()
            ->currentUser()
            ->with('userQuoteTemplates:id,name', 'templateFieldType');
    }

    public function data(): Collection
    {
        $types = $this->templateFieldType->get();

        return collect(compact('types'));
    }

    public function all()
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

        $items = $this->searchOnElasticsearch($this->templateField, $searchableFields, $query);

        $activated = $this->buildQuery($this->templateField, $items, function ($query) {
            return $query->currentUser()->with('userQuoteTemplates:id,name', 'templateFieldType')->activated();
        });
        $deactivated = $this->buildQuery($this->templateField, $items, function ($query) {
            return $query->currentUser()->with('userQuoteTemplates:id,name', 'templateFieldType')->deactivated();
        });

        return $activated->union($deactivated)->apiPaginate();
    }

    public function find(string $id): TemplateField
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StoreTemplateFieldRequest $request): TemplateField
    {
        return $request->user()->templateFields()
            ->create($request->validated())->load('userQuoteTemplates:id,name', 'templateFieldType');
    }

    public function update(UpdateTemplateFieldRequest $request, string $id): TemplateField
    {
        $templateField = $this->find($id);
        $templateField->update($request->validated());

        return $templateField;
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
            \App\Http\Query\OrderByCreatedAt::class
        ];
    }
}
