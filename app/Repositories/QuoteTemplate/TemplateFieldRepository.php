<?php

namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface;
use App\Http\Requests\QuoteTemplate\{
    StoreTemplateFieldRequest,
    UpdateTemplateFieldRequest
};
use App\Models\QuoteTemplate\{
    TemplateField,
    TemplateFieldType
};
use App\Repositories\SearchableRepository;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};
use Illuminate\Support\Collection as SupportCollection;

class TemplateFieldRepository extends SearchableRepository implements TemplateFieldRepositoryInterface
{
    const SYSTEM_FIELDS_CACHE_KEY = 'system-template-fields';

    protected TemplateField $templateField;

    protected TemplateFieldType $templateFieldType;

    public function __construct(TemplateField $templateField, TemplateFieldType $templateFieldType)
    {
        $this->templateField = $templateField;
        $this->templateFieldType = $templateFieldType;
    }

    public function userQuery(): Builder
    {
        return $this->templateField->query()
            ->with('userQuoteTemplates:id,name', 'templateFieldType');
    }

    public function data(): SupportCollection
    {
        $types = $this->templateFieldType->get();

        return collect(compact('types'));
    }

    public function allSystem(): Collection
    {
        return cache()->sear(
            static::SYSTEM_FIELDS_CACHE_KEY,
            fn () => $this->templateField->system()->with('templateFieldType')->ordered()->get()
        );
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
        return tap($this->find($id))->update($request->validated());
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

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->templateField;
    }

    protected function searchableFields(): array
    {
        return [
            'header^5', 'name^4', 'created_at^3'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('userQuoteTemplates:id,name', 'templateFieldType');
    }
}
