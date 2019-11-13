<?php

namespace App\Traits\Search;

use App\Observers\SearchObserver;

trait Searchable
{
    public static function bootSearchable()
    {
        if (config('services.search.enabled')) {
            static::observe(SearchObserver::class);
        }
    }

    public function getSearchIndex()
    {
        return $this->getTable();
    }

    public function getSearchType()
    {
        if (property_exists($this, 'useSearchType')) {
            return $this->useSearchType;
        }

        return $this->getTable();
    }

    public function toSearchArray()
    {
        return $this->toArray();
    }
}
