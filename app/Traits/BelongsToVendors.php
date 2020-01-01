<?php

namespace App\Traits;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

        if (blank(array_flatten($changes))) {
            return;
        }

        $newVendors = $this->load('vendors')->vendors;

        activity()
            ->on($this)
            ->withAttribute('vendors', $newVendors->toString('name'), $oldVendors->toString('name'))
            ->log('updated');
    }
}
