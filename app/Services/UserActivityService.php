<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserActivityService
{
    public function logoutInactive(): int
    {
        $query = User::where(fn (Builder $query) => $query->whereRaw('last_activity_at <= DATE_SUB(NOW(), INTERVAL ? MINUTE)', [config('activity.expires_in', 60)])->orWhereNull('last_activity_at'))
            ->where('already_logged_in', true)->toBase();

        $ids = $query->pluck('id');

        if (empty($ids)) {
            return 0;
        }

        foreach ($ids as $id) {
            $lock = UserRepository::lock($id);

            $lock->get(
                fn () => DB::transaction(fn () => (clone $query)->where('id', $id)->update(['already_logged_in' => false]))
            );
        }

        return count($ids);
    }
}
