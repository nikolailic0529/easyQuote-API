<?php

namespace App\Traits;

use App\Models\Vendor;

trait BelongsToVendors
{
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class);
    }

    public function syncVendors(?array $vendors, bool $detach = true)
    {
        if (blank($vendors)) {
            return;
        }

        $oldVendors = $this->vendors;

        $changes = $this->vendors()->sync($vendors, $detach);

        if (blank(array_flatten($changes))) {
            return $changes;
        }

        $newVendors = $this->load('vendors')->vendors;

        activity()
            ->on($this)
            ->withAttribute('vendors', $newVendors->toString('name'), $oldVendors->toString('name'))
            ->log('updated');
    }
}
