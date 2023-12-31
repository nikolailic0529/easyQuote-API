<?php

namespace App\Domain\Vendor\Concerns;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

trait BelongsToVendors
{
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }

    public function syncVendors(?array $vendors, bool $detach = true): void
    {
        if (blank($vendors)) {
            return;
        }

        $oldVendors = $this->vendors;

        $changes = $this->vendors()->sync($vendors, $detach);

        if (blank(Arr::flatten($changes))) {
            return;
        }

        $newVendors = $this->load('vendors')->vendors;

        activity()
            ->on($this)
            ->withAttribute('vendors', $newVendors->toString('name'), $oldVendors->toString('name'))
            ->queue('updated');
    }
}
