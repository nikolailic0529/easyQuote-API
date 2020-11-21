<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface ReindexQuery
{
    public static function reindexQuery(): Builder;
}