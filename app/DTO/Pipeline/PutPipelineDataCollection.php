<?php

namespace App\DTO\Pipeline;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class PutPipelineDataCollection extends DataTransferObjectCollection
{
    public function current(): PutPipelineData
    {
        return parent::current();
    }
}
