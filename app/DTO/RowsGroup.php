<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Spatie\DataTransferObject\DataTransferObject;
use Illuminate\Support\Str;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

class RowsGroup extends FlexibleDataTransferObject implements Responsable
{
    public string $id;

    public string $name;

    public string $search_text;

    public bool $is_selected = true;

    /** @var string[] */
    public array $rows_ids = [];

    public static function make(array $attributes)
    {
        return new static($attributes + ['id' => Str::uuid()->__toString()]);
    }

    public function toResponse($request)
    {
        return $this->toArray();
    }
}