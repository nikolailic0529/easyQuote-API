<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface HasReindexQuery
{
    public static function reindexQuery(): Builder;
}