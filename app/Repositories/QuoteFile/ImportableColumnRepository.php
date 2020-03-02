<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Models\QuoteFile\ImportableColumn;
use App\Repositories\SearchableRepository;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Arr, DB;
use Webpatser\Uuid\Uuid;

class ImportableColumnRepository extends SearchableRepository implements ImportableColumnRepositoryInterface
{
    const CACHE_KEY_SYSTEM_COLS = 'importable-columns:system';

    /** @var \App\Models\QuoteFile\ImportableColumn */
    protected ImportableColumn $importableColumn;

    public function __construct(ImportableColumn $importableColumn)
    {
        $this->importableColumn = $importableColumn;
    }

    public function query(): Builder
    {
        return $this->importableColumn->query();
    }

    public function regularQuery(): Builder
    {
        return $this->query()->where('is_temp', false);
    }

    public function find(string $id): ImportableColumn
    {
        return $this->query()->whereId($id)->firstOrFail();
    }

    public function findByIds(iterable $ids)
    {
        if ($ids instanceof Arrayable) {
            $ids = $ids->toArray();
        }

        return $this->query()->whereIn('id', $ids)->get();
    }

    public function all()
    {
        return $this->importableColumn->ordered()->with('aliases')->get();
    }

    public function paginate()
    {
        return parent::all();
    }

    public function allSystem()
    {
        return cache()->sear(
            self::CACHE_KEY_SYSTEM_COLS,
            fn () => $this->importableColumn->ordered()->system()->with('aliases')->get()
        );
    }

    public function userColumns(array $alises = [])
    {
        return $this->importableColumn->nonSystem()
            ->whereHas('aliases', fn ($query) => $query->whereIn('alias', $alises))
            ->with(['aliases' => fn ($query) => $query->groupBy('alias')])
            ->get();
    }

    public function allNames()
    {
        return $this->importableColumn->system()->pluck('name')->toArray();
    }

    public function findByName(string $name): ImportableColumn
    {
        return $this->importableColumn->system()->whereName($name)->firstOrFail();
    }

    public function firstOrCreate(array $attributes, array $values = [], ?Closure $scope = null): ImportableColumn
    {
        $query = $this->importableColumn->query();

        if ($scope instanceof Closure) {
            call_user_func($scope, $query);
        }

        if (!is_null($instance = $query->where($attributes)->orderBy('is_temp')->first())) {
            return $instance;
        }

        /** We are marking new column as temporary to prevent displaying columns which are created during import in the regular query. */
        $values['is_temp'] = true;

        return tap($query->newModelInstance($attributes + $values), function ($instance) {
            $instance->disableLogging();
            $instance->disableReindex();
            $instance->save();
        });
    }

    public function create(array $attributes): ImportableColumn
    {
        return tap($this->importableColumn->make($attributes), function (ImportableColumn $importableColumn) use ($attributes) {
            $importableColumn->disableLogging()->save();

            $aliases = static::parseAliasesAttributes(data_get($attributes, 'aliases'));
            $importableColumn->aliases()->createMany($aliases);

            $importableColumn->load('aliases');

            activity()->on($importableColumn)
                ->withProperty('attributes', $importableColumn->logChanges($importableColumn))
                ->queue('created');
        });
    }

    public function update(array $attributes, string $id): ImportableColumn
    {
        return tap($this->find($id), function (ImportableColumn $importableColumn) use ($attributes) {
            DB::transaction(function () use ($importableColumn, $attributes) {
                $oldAttributes = $importableColumn->logChanges($importableColumn);

                $importableColumn->disableLogging()->update($attributes);

                $aliases = data_get($attributes, 'aliases');
                $importableColumn->aliases()->whereNotIn('alias', $aliases)->delete();

                $existingAliases = $importableColumn->aliases()->whereIn('alias', $aliases)->pluck('alias')->flip();

                $creatingAliases = Arr::where($aliases, fn ($alias) => !$existingAliases->has($alias));

                $importableColumn->aliases()->createMany(static::parseAliasesAttributes($creatingAliases));

                $importableColumn->load('aliases');

                if ($importableColumn->isSystem()) {
                    $this->flushSystemImportableColumnsCache();
                }

                activity()->on($importableColumn)
                    ->withProperties(['attributes' => $importableColumn->logChanges($importableColumn), 'old' => $oldAttributes])
                    ->queue('updated');
            });
        });
    }

    public function delete(string $id): bool
    {
        return tap(
            $this->find($id),
            fn (ImportableColumn $importableColumn) => $importableColumn->aliases()->delete()
        )->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    protected function searchableModel(): Model
    {
        return $this->importableColumn;
    }

    protected function filterableQuery()
    {
        return [
            $this->regularQuery()->with('country')->activated(),
            $this->regularQuery()->with('country')->deactivated()
        ];
    }

    protected function searchableQuery()
    {
        return $this->regularQuery()->with('country');
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\ImportableColumn\OrderByHeader::class,
            \App\Http\Query\ImportableColumn\OrderByType::class,
            \App\Http\Query\ImportableColumn\OrderByCountryName::class
        ];
    }

    protected function searchableFields(): array
    {
        return ['header^5', 'type^4', 'aliases^4', 'country_name^3', 'created_at'];
    }

    private static function flushSystemImportableColumnsCache(): void
    {
        cache()->forget(self::CACHE_KEY_SYSTEM_COLS);
    }

    private static function parseAliasesAttributes($aliases): array
    {
        if (!is_array($aliases)) {
            return [];
        }

        return array_map(fn ($alias) => compact('alias'), $aliases);
    }
}
