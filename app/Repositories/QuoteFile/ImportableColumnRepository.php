<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Models\QuoteFile\ImportableColumn;
use App\Repositories\SearchableRepository;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ImportableColumnRepository extends SearchableRepository implements ImportableColumnRepositoryInterface
{
    const CACHE_KEY_SYSTEM_COLS = 'importable-columns:system';

    /** @var \App\Models\QuoteFile\ImportableColumn */
    protected $importableColumn;

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
        return cache()->sear(self::CACHE_KEY_SYSTEM_COLS, function () {
            return $this->importableColumn->ordered()->system()->with('aliases')->get();
        });
    }

    public function userColumns(array $alises = [])
    {
        return $this->importableColumn->nonSystem()->whereHas('aliases', function ($query) use ($alises) {
            $query->whereIn('alias', $alises);
        })->with(['aliases' => function ($query) {
            $query->groupBy('alias');
        }])->get();
    }

    public function allNames()
    {
        $names = $this->importableColumn->system()->select('name')->get()->toArray();

        return collect($names)->flatten()->toArray();
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

        if (! is_null($instance = $query->where($attributes)->first())) {
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
        return $this->importableColumn->create($attributes);
    }

    public function update(array $attributes, string $id): ImportableColumn
    {
        return tap($this->find($id), function ($importableColumn) use ($attributes) {
            $importableColumn->update($attributes);
        });
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

    protected function searchableModel(): Model
    {
        return $this->importableColumn;
    }

    protected function filterableQuery()
    {
        return $this->regularQuery();
    }

    protected function searchableQuery()
    {
        return $this->regularQuery();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class
        ];
    }

    protected function searchableFields(): array
    {
        return ['header', 'name'];
    }
}
