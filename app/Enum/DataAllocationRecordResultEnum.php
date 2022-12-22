<?php

namespace App\Enum;

enum DataAllocationRecordResultEnum: string
{
    case Unprocessed = 'Unprocessed';
    case NewRecord = 'NewRecord';
    case UpdatedRecord = 'UpdatedRecord';
    case Failed = 'Failed';
}