<?php

namespace App\Repositories\Concerns;

use App\Models\Quote\BaseQuote;
use Illuminate\Support\Collection;
use App\Http\Requests\{
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Models\Quote\QuoteVersion;
use Illuminate\Database\Query\Builder;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

trait ManagesGroupDescription
{
    use FetchesGroupDescription;

    public function retrieveRowsGroups($quote, ?string $groupName = null): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            $quote = $this->findVersion($quote);
        }

        $groupableRows = $this->retrieveRows($quote, function (Builder $builder) use ($groupName) {
            $builder
                ->when(filled($groupName), fn (Builder $builder) => $builder->whereGroupName($groupName))
                ->whereNotNull('group_name')
                ->addSelect('group_name', 'price')
                ->orderBy('group_name');
        });

        return static::mapGroupDescriptionWithRows($quote, $groupableRows);
    }

    public function searchRows($quote, string $search = '', ?string $groupId = null): Collection
    {
        if (!$quote instanceof QuoteVersion) {
            $quote = $this->findVersion($quote);
        }

        $inputs = collect(static::fetchRowsSearchInput($search));

        $groupName = !is_null($groupId) ? data_get($quote->findGroupDescription($groupId), 'name') : null;

        return $this->retrieveRows(
            $quote,
            fn (Builder $builder) =>
            $builder->where(fn (Builder $query) =>
                $query->whereRaw("columns_data->'$.*.value' like ?", ['%' . $inputs->shift() . '%'])
                    ->tap(function (Builder $query) use ($inputs) {
                        $inputs->each(fn ($input) => $query->orWhereRaw("columns_data->'$.*.value' like ?", ['%' . $input . '%']));
                    })
                    ->when(filled($groupName), fn (Builder $query) => $query->orWhere('group_name', $groupName))
                    ->addSelect(DB::raw("TRUE AS `is_selected`")))
        );
    }

    public function findGroupDescription(string $id, string $quote_id): Collection
    {
        $quote = $this->findVersion($quote_id);

        $group = $quote->findGroupDescription($id);

        $groupRows = $this->getGroupDescriptionRows($quote, data_get($group, 'name'));

        return static::unionGroupRowsWithDescription($group, $groupRows);
    }

    public function createGroupDescription($attributes, string $quote_id): Collection
    {
        if ($attributes instanceof \Illuminate\Http\Request) {
            $attributes = $attributes->validated();
        }

        throw_unless(is_array($attributes), new \InvalidArgumentException(INV_ARG_RA_01));

        $quote = $this->find($quote_id);
        $version = $this->createNewVersionIfNonCreator($quote);

        $initialGroupDescription = $this->retrieveRowsGroups($version);

        $data = collect($attributes);
        $group = $data->only(['name', 'search_text'])->merge(['id' => Uuid::generate(4)->string, 'is_selected' => false]);
        $rows = $data->get('rows', []);

        /** We are updating group description rows only in case when new version was not created, because there are different rows ids. */
        if ($quote->wasNotCreatedNewVersion) {
            $version->rowsData()->whereIn('imported_rows.id', $rows)->update(['group_name' => $group->get('name')]);
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
    }

    public function selectGroupDescription(array $ids, string $quote): bool
    {
        $quote = $this->createNewVersionIfNonCreator($this->find($quote));

        $groups = Collection::wrap($quote->group_description)
            ->transform(fn ($group) => Arr::set($group, 'is_selected', in_array(Arr::get($group, 'id'), $ids)));

        $quote->group_description = $groups->values();

        return tap($quote)->forgetCachedComputableRows()->save();
    }

    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, string $id, string $quote_id): bool
    {
        $quote = $this->find($quote_id);
        $version = $this->createNewVersionIfNonCreator($quote);

        $initialGroupDescription = $this->retrieveRowsGroups($version);

        $data = collect($request->validated());
        $group = $data->only(['name', 'search_text'])->toArray();
        $rows = $data->get('rows', []);

        $group_key = $this->findGroupDescriptionKey($id, $version);

        $updatableGroup = $request->group();

        /** We are updating group description rows only in case when new version was not created, because there are different rows ids. */
        if ($quote->wasNotCreatedNewVersion) {
            $version->rowsData()->whereGroupName($updatableGroup['name'])->update(['group_name' => null]);
            $version->rowsData()->whereIn('imported_rows.id', $rows)->update(['group_name' => $group['name']]);
        }

        $updatedGroup = array_merge($updatableGroup, $group);

        $version->group_description = collect($version->group_description)->put($group_key, $updatedGroup)->values();

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
    }

    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, string $quote_id): bool
    {
        $quote = $this->find($quote_id);
        $version = $this->createNewVersionIfNonCreator($quote);

        $initialGroupDescription = $this->retrieveRowsGroups($version);

        $fromGroupName = $request->fromGroupName();
        $toGroupName = $request->fromGroupName();

        abort_if(count(array_filter([$fromGroupName, $toGroupName])) < 2, 404, QG_FTNF_01);

        /** We are updating group description rows only in case when new version was not created, because there are different rows ids. */
        if ($quote->wasNotCreatedNewVersion) {
            $version->rowsData()->whereGroupName($fromGroupName)
                ->whereIn('imported_rows.id', $request->rows)
                ->update(['group_name' => $toGroupName]);
        }

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
    }

    public function deleteGroupDescription(string $id, string $quote_id): bool
    {
        $quote = $this->createNewVersionIfNonCreator($this->find($quote_id));

        $initialGroupDescription = $this->retrieveRowsGroups($quote);

        $group_description = Collection::wrap($quote->group_description);

        $group_key = $this->findGroupDescriptionKey($id, $quote);

        $removableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($removableGroup['name'])->update(['group_name' => null]);

        $group_description->forget($group_key);

        $quote->group_description = $group_description->isEmpty() ? null : $group_description->values();

        if (blank($quote->group_description)) {
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
    }

    protected function findGroupDescriptionKey(string $id, BaseQuote $quote)
    {
        return tap($quote->findGroupDescriptionKey($id), fn ($key) => abort_if($key === false, 404, QG_NF_01));
    }

    protected function getGroupDescriptionRows(BaseQuote $quote, ?string $groupName)
    {
        return $this->retrieveRows(
            $quote,
            fn (Builder $builder) =>
            $builder->whereGroupName($groupName)->whereNotNull('group_name')->addSelect('group_name', 'price')
        );
    }
}
