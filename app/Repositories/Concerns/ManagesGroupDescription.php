<?php

namespace App\Repositories\Concerns;

use App\Models\Quote\BaseQuote;
use Illuminate\Support\Collection;
use App\Http\Requests\{
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\UpdateGroupDescriptionRequest
};
use Webpatser\Uuid\Uuid;

trait ManagesGroupDescription
{
    public function rowsGroups(string $id): Collection
    {
        $quote = $this->findVersion($id);
        $grouped_rows = $quote->groupedRows()->get();
        $groups_meta = $quote->getGroupDescriptionWithMeta();

        return $grouped_rows->rowsToGroups('group_name', $groups_meta)
            ->exceptEach('group_name')
            ->sortByFields($quote->sort_group_description);
    }

    public function findGroupDescription(string $id, string $quote_id): Collection
    {
        $quote = $this->findVersion($quote_id);

        $group_key = $this->findGroupDescriptionKey($id, $quote);

        $group = collect($quote->group_description)->get($group_key);
        $groups_meta = $quote->getGroupDescriptionWithMeta(null, false, $group['name']);

        $group = $quote->groupedRows(null, false, $group['name'])->get()
            ->rowsToGroups('group_name', $groups_meta)->exceptEach('group_name')
            ->first();

        return $group;
    }

    public function createGroupDescription($attributes, string $quote_id): Collection
    {
        if ($attributes instanceof \Illuminate\Http\Request) {
            $attributes = $attributes->validated();
        }

        throw_unless(is_array($attributes), new \InvalidArgumentException(INV_ARG_RA_01));

        $quote = $this->findVersion($quote_id);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $data = collect($attributes);
        $group = $data->only(['name', 'search_text'])->prepend(Uuid::generate(4)->string, 'id');

        $rows = $data->get('rows', []);

        $quote->rowsData()->whereIn('imported_rows.id', $rows)
            ->update(['group_name' => $group->get('name')]);

        $quote->group_description = collect($quote->group_description)->push($group)->values();

        $quote->save();

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->queue('updated');

        $quote->forgetCachedComputableRows();

        return $group;
    }

    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, string $id, string $quote_id): bool
    {
        $quote = $this->findVersion($quote_id);

        $group_description = collect($quote->group_description);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $data = collect($request->validated());
        $group = $data->only(['name', 'search_text'])->toArray();
        $rows = $data->get('rows', []);

        $group_key = $this->findGroupDescriptionKey($id, $quote);

        $updatableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($updatableGroup['name'])
            ->update(['group_name' => null]);

        $quote->rowsData()->whereIn('imported_rows.id', $rows)
            ->update(['group_name' => $group['name']]);

        $updatedGroup = array_merge($updatableGroup, $group);

        $quote->group_description = collect($quote->group_description)->put($group_key, $updatedGroup)->values();

        $saved = $quote->save();

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->queue('updated');

        $quote->forgetCachedComputableRows();

        return $saved;
    }

    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, string $quote_id): bool
    {
        $quote = $this->findVersion($quote_id);

        $group_description = collect($quote->group_description);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $from_group_key = $quote->findGroupDescriptionKey($request->from_group_id);
        $to_group_key = $quote->findGroupDescriptionKey($request->to_group_id);

        abort_if(($from_group_key === false || $to_group_key === false), 404, QG_FTNF_01);

        $from_group = $group_description->get($from_group_key);
        $to_group = $group_description->get($to_group_key);

        $updated = $quote->rowsData()->whereGroupName($from_group['name'])
            ->whereIn('imported_rows.id', $request->rows)
            ->update(['group_name' => $to_group['name']]);

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->queue('updated');

        $quote->forgetCachedComputableRows();

        return $updated;
    }

    public function deleteGroupDescription(string $id, string $quote_id): bool
    {
        $quote = $this->findVersion($quote_id);

        $group_description = collect($quote->group_description);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $group_key = $this->findGroupDescriptionKey($id, $quote);

        $removableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($removableGroup['name'])
            ->update(['group_name' => null]);

        $group_description->forget($group_key);

        $quote->group_description = $group_description->isEmpty() ? null : $group_description->values();

        if (blank($quote->group_description)) {
            $quote->sort_group_description = null;
        }

        $saved = $quote->save();

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->queue('updated');

        $quote->forgetCachedComputableRows();

        return $saved;
    }

    protected function findGroupDescriptionKey(string $id, BaseQuote $quote)
    {
        return tap($quote->findGroupDescriptionKey($id), function ($key) {
            abort_if($key === false, 404, QG_NF_01);
        });
    }
}
