<?php

namespace App\Domain\Rescue\Concerns;

use App\Domain\DocumentMapping\Collections\MappedRows;
use App\Domain\Rescue\DataTransferObjects\RowsGroup;
use App\Domain\Rescue\Exceptions\GroupDescription;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\{QuoteVersion};
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ManagesGroupDescription
{
    use FetchesGroupDescription;

    /** @param QuoteVersion|Quote $quote */
    public function retrieveRowsGroups(BaseQuote $quote): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            /** @var \App\Domain\Rescue\Models\BaseQuote */
            $quote = $quote->activeVersionOrCurrent;
        }

        $rows = $quote->group_description->pluck('rows_ids')->collapse();

        $groupedRows = (new QuoteQueries())
            ->mappedOrderedRowsQuery($quote)
            ->whereIn('id', $rows)
            ->addSelect('price', DB::raw('1 as `is_selected`'))
            ->get();

        $groupedRows = MappedRows::make($groupedRows);

        return static::mapGroupDescriptionWithRows($quote, $groupedRows);
    }

    public function searchRows(BaseQuote $quote, string $search = '', ?string $groupId = null): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            /** @var \App\Domain\Rescue\Models\BaseQuote */
            $quote = $quote->activeVersionOrCurrent;
        }

        $inputs = collect(static::fetchRowsSearchInput($search));

        $rowsIds = transform($groupId, fn () => $quote->group_description->firstWhere('id', $groupId)->rows_ids ?? []);

        $rows = (new QuoteQueries())
            ->mappedOrderedRowsQuery($quote)
            ->where(fn (Builder $query) => $query->whereRaw("columns_data->'$.*.value' like ?", ['%'.$inputs->shift().'%'])
                ->tap(function (Builder $query) use ($inputs) {
                    $inputs->each(fn ($input) => $query->orWhereRaw("columns_data->'$.*.value' like ?", ['%'.$input.'%']));
                })
                ->when($rowsIds !== null, fn (Builder $query) => $query->orWhereIn('id', $rowsIds))
                ->addSelect(DB::raw('TRUE AS `is_selected`')))
            ->get();

        return MappedRows::make($rows);
    }

    /**
     * @param Quote|QuoteVersion $quote
     */
    public function findGroupDescription(string $id, BaseQuote $quote): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            $quote = $quote->activeVersionOrCurrent;
        }

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
        /** @var BaseQuote */
        $version = $this->createNewVersionIfNonCreator($quote);

        $lock = Cache::lock(Lock::UPDATE_QUOTE($version->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            $initialGroupDescription = $this->retrieveRowsGroups($version);

            $group = RowsGroup::make([
                'name' => Arr::get($attributes, 'name'),
                'search_text' => Arr::get($attributes, 'search_text'),
                'rows_ids' => Arr::get($attributes, 'rows') ?? Arr::get($attributes, 'rows_ids') ?? [],
            ]);

            if ($quote->wasCreatedNewVersion) {
                $replicatedRows = (new QuoteQueries())->mappedOrderedRowsQuery($version)->whereIn('replicated_row_id', $group->rows_ids)->pluck('id');

                $group->rows_ids = $replicatedRows->all();
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

            DB::commit();

            return $group;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function selectGroupDescription(array $ids, Quote $quote): bool
    {
        /** @var \App\Domain\Rescue\Models\BaseQuote */
        $quote = $this->createNewVersionIfNonCreator($quote);

        $lock = Cache::lock(Lock::UPDATE_QUOTE($quote->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            $quote->group_description->each(function (RowsGroup $group) use ($ids) {
                $group->is_selected = in_array($group->id, $ids);
            });

            $quote->save();

            DB::commit();

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function updateGroupDescription(string $id, Quote $quote, array $attributes): bool
    {
        /** @var BaseQuote */
        $version = $this->createNewVersionIfNonCreator($quote);

        $lock = Cache::lock(Lock::UPDATE_QUOTE($version->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            $initialGroupDescription = $this->retrieveRowsGroups($version);

            /** @var \App\Domain\Rescue\DataTransferObjects\RowsGroup */
            $updatableGroup = $version->group_description->firstWhere('id', $id);

            if ($updatableGroup === null) {
                GroupDescription::notFound();
            }

            tap($updatableGroup, function (RowsGroup $updatableGroup) use ($quote, $version, $attributes) {
                $updatableGroup->name = Arr::get($attributes, 'name');
                $updatableGroup->search_text = Arr::get($attributes, 'search_text');
                $updatableGroup->rows_ids = Arr::get($attributes, 'rows') ?? Arr::get($attributes, 'rows_ids') ?? [];

                if ($quote->wasCreatedNewVersion) {
                    $replicatedRows = (new QuoteQueries())->mappedOrderedRowsQuery($version)->whereIn('replicated_row_id', $updatableGroup->rows_ids)->pluck('id');

                    $updatableGroup->rows_ids = $replicatedRows->all();
                }
            });

            $saved = $version->save();

            $newGroupDescription = $this->retrieveRowsGroups($version);

            DB::commit();

            activity()
                ->on($version)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            return $saved;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function moveGroupDescriptionRows(Quote $quote, array $attributes): bool
    {
        /** @var BaseQuote */
        $version = $this->createNewVersionIfNonCreator($quote);

        $lock = Cache::lock(Lock::UPDATE_QUOTE($version->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            $initialGroupDescription = $this->retrieveRowsGroups($version);

            /** @var RowsGroup */
            $fromGroup = $version->group_description->firstWhere('id', Arr::get($attributes, 'from_group_id'));

            /** @var \App\Domain\Rescue\DataTransferObjects\RowsGroup */
            $toGroup = $version->group_description->firstWhere('id', Arr::get($attributes, 'to_group_id'));

            /** @var array */
            $moveRows = $attributes['rows'] ?? [];

            abort_if(count(array_filter([$fromGroup, $toGroup])) < 2, 404, QG_FTNF_01);

            if ($quote->wasCreatedNewVersion) {
                /** @var \Illuminate\Support\Collection */
                $replicatedRows = (new QuoteQueries())->mappedOrderedRowsQuery($version)->whereIn('replicated_row_id', array_merge($fromGroup->rows_ids, $toGroup->rows_ids, $moveRows))
                    ->get(['id', 'replicated_row_id']);

                $fromGroup->rows_ids = $replicatedRows->whereIn('replicated_row_id', $fromGroup->rows_ids)->pluck('id')->all();
                $toGroup->rows_ids = $replicatedRows->whereIn('replicated_row_id', $toGroup->rows_ids)->pluck('id')->all();
                $moveRows = $replicatedRows->whereIn('replicated_row_id', $moveRows)->pluck('id')->all();
            }

            $fromGroup->rows_ids = Collection::wrap($fromGroup->rows_ids)->reject(fn ($id) => in_array($id, $moveRows))->values()->toArray();

            $toGroup->rows_ids = Collection::wrap($toGroup->rows_ids)->merge($moveRows)->filter()->flip()->flip()->values()->toArray();

            $replaceGroups = Collection::wrap([$fromGroup, $toGroup])->keyBy('id');

            $version->group_description = $version->group_description->keyBy('id')->merge($replaceGroups)->values();

            $version->save();

            $newGroupDescription = $this->retrieveRowsGroups($version);

            DB::commit();

            activity()
                ->on($version)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function deleteGroupDescription(string $id, Quote $quote): bool
    {
        /** @var BaseQuote */
        $quote = $this->createNewVersionIfNonCreator($quote);

        $lock = Cache::lock(Lock::UPDATE_QUOTE($quote->getKey()), 10);

        $lock->block(30);

        try {
            $initialGroupDescription = $this->retrieveRowsGroups($quote);

            $groupDescription = $quote->group_description;

            $quote->group_description = $groupDescription->keyBy('id')->forget($id)->values()->whenEmpty(fn () => null);

            if ($quote->group_description === null) {
                $quote->sort_group_description = null;
            }

            $saved = $quote->save();

            $newGroupDescription = $this->retrieveRowsGroups($quote);

            DB::commit();

            activity()
                ->on($quote)
                ->withAttribute(
                    'group_description',
                    $newGroupDescription->toString('name', 'total_count'),
                    $initialGroupDescription->toString('name', 'total_count')
                )
                ->queue('updated');

            return $saved;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function findGroupDescriptionKey(string $id, BaseQuote $quote)
    {
        $key = $quote->findGroupDescriptionKey($id);

        if ($key === false) {
            throw new NotFoundHttpException(QG_NF_01);
        }

        return $key;
    }

    protected function getGroupDescriptionRows(BaseQuote $quote, array $rowsIds)
    {
        $rows = (new QuoteQueries())
            ->mappedOrderedRowsQuery($quote)
            ->whereIn('id', $rowsIds)
            ->addSelect('price');

        return MappedRows::make($rows);
    }
}
