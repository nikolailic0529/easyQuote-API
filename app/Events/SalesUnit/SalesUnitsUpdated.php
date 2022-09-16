<?php

namespace App\Events\SalesUnit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;

final class SalesUnitsUpdated
{
    use SerializesModels;

    public function __construct(
        public readonly Collection $old,
        public readonly Collection $new
    ) {
    }
}
