<?php

namespace App\Domain\Template\Repositories;

use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Shared\Eloquent\Repository\Concerns\ResolvesImplicitModel;
use App\Domain\Shared\Eloquent\Repository\SearchableRepository;
use App\Domain\Template\Contracts\HpeContractTemplate as Contract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HpeContractTemplateRepository extends SearchableRepository implements Contract
{
    use ResolvesImplicitModel;
    const DESIGN_ATTRIBUTES = ['form_data', 'complete_design'];

    protected HpeContractTemplate $template;

    public function __construct(HpeContractTemplate $template)
    {
        $this->template = $template;
    }

    public function paginate(?string $search = null)
    {
        if (filled($search)) {
            return parent::search($search);
        }

        return parent::all();
    }

    public function findOrFail(string $id): HpeContractTemplate
    {
        return $this->template->whereKey($id)->firstOrFail();
    }

    public function findByCountry(string $country)
    {
        return $this->template->whereHas('countries', fn (Builder $query) => $query->whereKey($country))->get(['id', 'name']);
    }

    public function findBy(array $clause, ?bool $activated = null, array $columns = ['*']): Collection
    {
        return $this->template->query()
            ->when(
                Str::isUuid(Arr::get($clause, 'company_id')),
                fn ($query) => $query->whereHas('company', fn ($query) => $query->whereKey(Arr::get($clause, 'company_id')))
            )
            ->when(
                Str::isUuid(Arr::get($clause, 'country_id')),
                fn ($query) => $query->whereHas('countries', fn ($query) => $query->whereKey(Arr::get($clause, 'country_id'))),
            )
            ->when(
                is_bool($activated) && $activated,
                fn ($query) => $query->whereNotNull('activated_at')
            )
            ->when(
                is_bool($activated) && !$activated,
                fn ($query) => $query->whereNull('activated_at')
            )
            ->get($columns);
    }

    public function copy($id)
    {
        activity()->disableLogging();

        /** @var $replicatableTemplate HpeContractTemplate */
        $replicatableTemplate = $this->resolveModel($id);

        /** @var \App\Domain\HpeContract\Models\HpeContractTemplate */
        $template = DB::transaction(function () use ($replicatableTemplate) {
            $template = $replicatableTemplate->replicate(['user', 'countries', 'templateFields', 'is_active', 'is_system']);

            $countries = $replicatableTemplate->countries()->pluck('id')->toArray();

            return tap($template, function (HpeContractTemplate $template) use ($countries) {
                $template->save();
                $template->syncCountries($countries);
            });
        }, DB_TA);

        activity()->enableLogging();

        activity()
            ->on($template)
            ->withProperties([
                'old' => HpeContractTemplate::logChanges($replicatableTemplate),
                'attributes' => HpeContractTemplate::logChanges($template),
            ])
            ->queue('copied');

        return [$template->getKeyName() => $template->getKey()];
    }

    public function create(array $attributes): HpeContractTemplate
    {
        return DB::transaction(function () use ($attributes) {
            /** @var HpeContractTemplate */
            $template = tap($this->template->query()->make($attributes))->save();
            $template->syncCountries(data_get($attributes, 'countries') ?? []);

            return $template;
        }, DB_TA);
    }

    public function update($id, array $attributes): HpeContractTemplate
    {
        $model = $this->resolveModel($id);

        /** @var HpeContractTemplate */
        $template = DB::transaction(fn () => tap($model, function (HpeContractTemplate $template) use ($attributes) {
            $template->fill($attributes)->save();
            $template->syncCountries(data_get($attributes, 'countries') ?? []);
        }), DB_TA);

        /**
         * Determine that passed $attributes have only design related values (static::DESIGN_ATTRIBUTES).
         * Then if the passed $attributes have complete_design we'll perform record log with updated description.
         */
        if (
            Arr::has($attributes, static::DESIGN_ATTRIBUTES) &&
            blank(array_diff_key($attributes, array_flip(static::DESIGN_ATTRIBUTES)))
        ) {
            activity()->on($template)->queue('updated');
        }

        return $template;
    }

    public function delete($id): bool
    {
        $model = $this->resolveModel($id);

        return DB::transaction(fn () => $model->delete(), DB_TA);
    }

    public function activate($id): bool
    {
        $model = $this->resolveModel($id);

        return DB::transaction(fn () => $model->activate(), DB_TA);
    }

    public function deactivate($id): bool
    {
        $model = $this->resolveModel($id);

        return DB::transaction(fn () => $model->deactivate(), DB_TA);
    }

    public function model(): string
    {
        return HpeContractTemplate::class;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Domain\Template\Queries\Filters\OrderByCreatedAt::class,
            \App\Domain\Template\Queries\Filters\OrderByName::class,
            \App\Domain\Template\Queries\Filters\OrderByCompanyName::class,
            \App\Domain\Template\Queries\Filters\OrderByVendorName::class,
            \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy::class,
        ];
    }

    protected function filterableQuery()
    {
        $query = $this->template->query()
            ->select(...$this->qualifyColumns('id', 'name', 'is_system', 'vendor_id', 'company_id', 'created_at', 'activated_at'))
            ->with('company:id,name', 'countries:id,name', 'vendor:id,name');

        return [
            (clone $query)->activated(),
            (clone $query)->deactivated(),
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->template;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'countries.name^4', 'vendor.name^4', 'company.name^4', 'created_at^3',
        ];
    }

    protected function searchableScope($query)
    {
        return $query
            ->select('id', 'name', 'is_system', 'vendor_id', 'company_id', 'created_at', 'activated_at')
            ->with('company:id,name', 'countries:id,name', 'vendor:id,name');
    }

    protected function qualifyColumns(string ...$columns): array
    {
        return Collection::wrap($columns)->map(fn ($column) => $this->template->qualifyColumn($column))->toArray();
    }
}
