<?php

namespace App\Domain\Rescue\Quote;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property \App\Domain\Rescue\Models\BaseQuote $activeVersionOrCurrent
 * @property \App\Domain\Rescue\Models\BaseQuote $activeVersionFromSelection
 */
trait HasQuoteVersions
{
    public bool $wasCreatedNewVersion = false;

    protected ?\App\Domain\Rescue\Models\QuoteVersion $originalVersion = null;

    protected ?Collection $versionsSelection = null;

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Rescue\Models\QuoteVersion::class, 'active_version_id');
    }

    public function getActiveVersionOrCurrentAttribute()
    {
        $version = $this->activeVersion;

        return $version ?? $this;
    }

    public function versions(): HasMany
    {
        return $this->hasMany(\App\Domain\Rescue\Models\QuoteVersion::class);
    }

    public function getHasVersionsAttribute(): bool
    {
        return $this->versions->isNotEmpty();
    }

    public function getVersionsSelectionAttribute(): Collection
    {
        if (isset($this->versionsSelection)) {
            return $this->versionsSelection;
        }

        if ($this->versions->count() < 1) {
            return $this->versionsSelection = collect();
        }

        return $this->versionsSelection = (clone $this->versions)
            ->push($this)
            ->map(fn (\App\Domain\Rescue\Models\BaseQuote $quote) => [
                'id' => $quote->id,
                'user_id' => $quote->user_id,
                'name' => $quote->versionName,
                'is_using' => $this->isActiveVersion($quote),
                'is_original' => $quote instanceof \App\Domain\Rescue\Models\Quote,
                'updated_at' => $quote->updated_at,
            ])
            ->sortByDesc('updated_at')
            ->values();
    }

    public function getActiveVersionFromSelectionAttribute(): \App\Domain\Rescue\Models\BaseQuote
    {
        return $this->versions->firstWhere('id', $this->active_version_id) ?? $this;
    }

    public function setVersionsSelectionAttribute(Collection $value): void
    {
        $this->versionsSelection = $value;
    }

    public function isActiveVersion(\App\Domain\Rescue\Models\BaseQuote $quote): bool
    {
        if (is_null($this->active_version_id)) {
            return $quote->getKey() === $this->getKey();
        }

        return $quote->getKey() === $this->active_version_id;
    }
}
