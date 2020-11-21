<?php

namespace App\Repositories\Concerns;

use App\DTO\RowsGroup;
use App\Models\Quote\BaseQuote;
use Illuminate\Support\Collection;
use App\Http\Requests\{
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use App\Services\Exceptions\GroupDescription;
use Illuminate\Database\Query\Builder;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

trait ManagesGroupDescription
{
    use FetchesGroupDescription;

    public function retrieveRowsGroups(BaseQuote $quote): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            /** @var BaseQuote */
            $quote = $this->findVersion($quote);
        }

        $rows = $quote->group_description->pluck('rows_ids')->collapse();

        $groupableRows = $this->retrieveRows($quote, fn (Builder $builder) =>
        $builder
            ->whereIn('id', $rows)
            ->addSelect('price', DB::raw('1 as `is_selected`')));

        return static::mapGroupDescriptionWithRows($quote, $groupableRows);
    }

    public function searchRows(BaseQuote $quote, string $search = '', ?string $groupId = null): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            /** @var BaseQuote */
            $quote = $this->findVersion($quote);
        }

        $inputs = collect(static::fetchRowsSearchInput($search));

        $rowsIds = transform($groupId, fn () => $quote->group_description->firstWhere('id', $groupId)->rows_ids ?? []);

        return $this->retrieveRows(
            $quote,
            fn (Builder $builder) =>
            $builder->where(fn (Builder $query) =>
            $query->whereRaw("columns_data->'$.*.value' like ?", ['%' . $inputs->shift() . '%'])
                ->tap(function (Builder $query) use ($inputs) {
                    $inputs->each(fn ($input) => $query->orWhereRaw("columns_data->'$.*.value' like ?", ['%' . $input . '%']));
                })
                ->when($rowsIds !== null, fn (Builder $query) => $query->orWhereIn('id', $rowsIds))
                ->addSelect(DB::raw("TRUE AS `is_selected`")))
        );
    }

    public function findGroupDescription(string $id, BaseQuote $quote): Collection
    {
        /** @var BaseQuote */
        $quote = $this->findVersion($quote);

        /** @var RowsGroup */
        $group = $quote->group_description->firstWhere('id', $id);

        if ($group === null) {
            GroupDescription::notFound();
        }

        $groupRows = $this->getGroupDescriptionRows($quote, $group->rows_ids);

        return static::unionGroupRowsWithDescription($group->toArray(), $groupRows);
    }

    public function createGroupDescription(array $attributes, Quote $quote)
    {
        return DB::transaction(function () use ($attributes, $quote) {
            /** @var BaseQuote */
            $version = $this->createNewVersionIfNonCreator($quote);

            $initialGroupDescription = $this->retrieveRowsGroups($version);

            $group = RowsGroup::make([
                'name'          => Arr::get($attributes, 'name'),
                'search_text'   => Arr::get($attributes, 'search_text'),
                'rows_ids'      => Arr::get($attributes, 'rows') ?? Arr::get($attributes, 'rows_ids') ?? []
            ]);

            if ($quote->wasCreatedNewVersion) {
                /** @var \Illuminate\Support\Collection */
                $replicatedRows = $version->getMappedRows(
                    fn (Builder $builder) => $builder->select('id')->whereIn('replicated_row_id', $group->rows_ids)
                );

                $group->rows_ids = $replicatedRows->pluck('id')->toArray();
            }

            $version->group_description = Collection::wrap($version->group_description)->push($group)->values();

            $version->save();

            $newGroupDescription = $this->retrieveRowsGroups($version);

            activity()
                ->on($version)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            $version->forgetCachedComputableRows();

            return $group;
        });
    }

    public function selectGroupDescription(array $ids, Quote $quote): bool
    {
        return DB::transaction(function () use ($ids, $quote) {
            /** @var BaseQuote */
            $quote = $this->createNewVersionIfNonCreator($quote);

            $quote->group_description->each(function (RowsGroup $group) use ($ids) {
                $group->is_selected = in_array($group->id, $ids);
            });

            $quote->save();

            return tap($quote)->forgetCachedComputableRows()->save();
        });
    }

    public function updateGroupDescription(string $id, Quote $quote, array $attributes): bool
    {
        return DB::transaction(function () use ($id, $quote, $attributes) {
            /** @var BaseQuote */
            $version = $this->createNewVersionIfNonCreator($quote);

            $initialGroupDescription = $this->retrieveRowsGroups($version);

            /** @var RowsGroup */
            $updatableGroup = $version->group_description->firstWhere('id', $id);

            if ($updatableGroup === null) {
                GroupDescription::notFound();
            }

            tap($updatableGroup, function (RowsGroup $updatableGroup) use ($quote, $version, $attributes) {
                $updatableGroup->name = Arr::get($attributes, 'name');
                $updatableGroup->search_text = Arr::get($attributes, 'search_text');
                $updatableGroup->rows_ids = Arr::get($attributes, 'rows') ?? Arr::get($attributes, 'rows_ids') ?? [];

                if ($quote->wasCreatedNewVersion) {
                    /** @var \Illuminate\Support\Collection */
                    $replicatedRows = $version->getMappedRows(
                        fn (Builder $builder) => $builder->select('id')->whereIn('replicated_row_id', $updatableGroup->rows_ids)
                    );

                    $updatableGroup->rows_ids = $replicatedRows->pluck('id')->toArray();
                }
            });

            $saved = $version->save();

            $newGroupDescription = $this->retrieveRowsGroups($version);

            activity()
                ->on($version)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            $version->forgetCachedComputableRows();

            return $saved;
        });
    }

    public function moveGroupDescriptionRows(Quote $quote, array $attributes): bool
    {
        return DB::transaction(function () use ($quote, $attributes) {
            /** @var BaseQuote */
            $version = $this->createNewVersionIfNonCreator($quote);

            $initialGroupDescription = $this->retrieveRowsGroups($version);

            /** @var RowsGroup */
            $fromGroup = $version->group_description->firstWhere('id', Arr::get($attributes, 'from_group_id'));

            /** @var RowsGroup */
            $toGroup = $version->group_description->firstWhere('id', Arr::get($attributes, 'to_group_id'));

            /** @var array */
            $moveRows = $attributes['rows'] ?? [];

            abort_if(count(array_filter([$fromGroup, $toGroup])) < 2, 404, QG_FTNF_01);

            if ($quote->wasCreatedNewVersion) {
                /** @var \Illuminate\Support\Collection */
                $replicatedRows = $version->getMappedRows(
                    fn (Builder $builder) => $builder->select('id', 'replicated_row_id')->whereIn('replicated_row_id', array_merge($fromGroup->rows_ids, $toGroup->rows_ids, $moveRows))
                );

                $fromGroup->rows_ids = $replicatedRows->whereIn('replicated_row_id', $fromGroup->rows_ids)->pluck('id')->toArray();
                $toGroup->rows_ids = $replicatedRows->whereIn('replicated_row_id', $toGroup->rows_ids)->pluck('id')->toArray();
                $moveRows = $replicatedRows->whereIn('replicated_row_id', $moveRows)->pluck('id')->toArray();
            }

            $fromGroup->rows_ids = Collection::wrap($fromGroup->rows_ids)->reject(fn ($id) => in_array($id, $moveRows))->values()->toArray();

            $toGroup->rows_ids = Collection::wrap($toGroup->rows_ids)->merge($moveRows)->filter()->flip()->flip()->values()->toArray();

            $replaceGroups = Collection::wrap([$fromGroup, $toGroup])->keyBy('id');

            $version->group_description = $version->group_description->keyBy('id')->merge($replaceGroups)->values();

            $version->save();

            $newGroupDescription = $this->retrieveRowsGroups($version);

            activity()
                ->on($version)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            $version->forgetCachedComputableRows();

            return true;
        });
    }

    public function deleteGroupDescription(string $id, Quote $quote): bool
    {
        return DB::transaction(function () use ($id, $quote) {
            /** @var BaseQuote */
            $quote = $this->createNewVersionIfNonCreator($quote);

            $initialGroupDescription = $this->retrieveRowsGroups($quote);

            $groupDescription = $quote->group_description;

            $quote->group_description = $groupDescription->keyBy('id')->forget($id)->values()->whenEmpty(fn () => null);

            if ($quote->group_description === null) {
                $quote->sort_group_description = null;
            }

            $saved = $quote->save();

            $newGroupDescription = $this->retrieveRowsGroups($quote);

            activity()
                ->on($quote)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            $quote->forgetCachedComputableRows();

            return $saved;
        });
    }

    protected function findGroupDescriptionKey(string $id, BaseQuote $quote)
    {
        return tap($quote->findGroupDescriptionKey($id), fn ($key) => abort_if($key === false, 404, QG_NF_01));
    }

    protected function getGroupDescriptionRows(BaseQuote $quote, array $rowsIds)
    {
        return $this->retrieveRows(
            $quote,
            fn (Builder $builder) =>
            $builder->whereIn('id', $rowsIds)->addSelect('price')
        );
    }
}
