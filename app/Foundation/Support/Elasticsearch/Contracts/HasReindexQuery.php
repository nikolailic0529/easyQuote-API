<?php

namespace App\Foundation\Support\Elasticsearch\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface HasReindexQuery
{
    public static function reindexQuery(): Builder;
}
