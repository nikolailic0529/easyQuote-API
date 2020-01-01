<?php

namespace App\Traits\Quote;

use App\Models\Quote\{
    Quote,
    QuoteVersion,
    QuoteVersionPivot
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsToMany,
    HasOneThrough
};
use Illuminate\Support\Collection;

trait HasVersions
{
    protected $originalVersion;

    protected $versionsSelection;

    public function usingVersion(): HasOneThrough
    {
        return $this->hasOneThrough(QuoteVersion::class, QuoteVersionPivot::class, 'quote_id', 'id', 'id', 'version_id')
            ->where('quote_version.is_using', true)
            ->withDefault(function ($instance, $parent) {
                return $parent->makeVersionFromInstance($parent, $parent->exists);
            });
    }

    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(QuoteVersion::class, 'quote_version', 'quote_id', 'version_id')
            ->withPivot(['is_using', 'quote_id']);
    }

    public function attachNewVersion(QuoteVersion $version): void
    {
        $this->versions()->update(['is_using' => false]);
        $this->versions()->attach($version, ['is_using' => true]);
    }

    public function scopeNotVersion(Builder $query): Builder
    {
        return $query->where('is_version', false);
    }

    public function getHasVersionsAttribute(): bool
    {
        return (bool) $this->versions->count();
    }

    public function getOriginalVersionAttribute(): QuoteVersion
    {
        if (isset($this->originalVersion)) {
            return $this->originalVersion;
        }

        $is_using_original = !$this->versions->pluck('pivot.is_using')->contains(true);

        return $this->originalVersion = $this->makeVersionFromInstance($this)
            ->forceFill(compact('is_using_original'));
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
            ->push($this->getAttribute('original_version'))
            ->map->toSelectionArray();
    }

    public function setVersionsSelectionAttribute(Collection $value)
    {
        $this->versionsSelection = $value;
    }

    /**
     * Make new instance of the given model.
     *
     * @param Model $instance
     * @param boolean $exists
     * @return Model
     */
    protected static function makeVersionFromInstance(Model $instance, bool $exists = true): Model
    {
        return app(QuoteVersion::class)->newInstance([], $exists)->setRawAttributes($instance->getAttributes())
            ->setRelations($instance->getRelations());
    }
}
