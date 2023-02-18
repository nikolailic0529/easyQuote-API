<?php

namespace App\Domain\Pipeline\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class PutPipelineDataCollection extends DataTransferObjectCollection
{
    public function current(): PutPipelineData
    {
        return parent::current();
    }
}
