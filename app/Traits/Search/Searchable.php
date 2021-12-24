<?php

namespace App\Traits\Search;

use App\Observers\SearchObserver;

trait Searchable
{
    protected bool $reindexEnabled = true;

    public static function bootSearchable()
    {
        if (!app()->runningUnitTests() && config('services.elasticsearch.enabled')) {
            static::observe(SearchObserver::class);
        }
    }

    public function disableReindex(): self
    {
        $this->reindexEnabled = false;

        return $this;
    }

    public function enableReindex(): self
    {
        $this->reindexEnabled = true;

        return $this;
    }

    public function reindexEnabled(): bool
    {
        return $this->reindexEnabled === true;
    }

    public function reindexDisabled(): bool
    {
        return $this->reindexEnabled === false;
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function getSearchType(): string
    {
        if (property_exists($this, 'useSearchType')) {
            return $this->useSearchType;
        }

        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return $this->toArray();
    }
}
