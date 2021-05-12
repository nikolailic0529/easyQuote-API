<?php

namespace App\DTO;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class ProcessableDistribution extends DataTransferObject
{
    public string $id;

    public string $distributor_file_id;

    public ?int $distributor_file_page = null;

    public ?string $schedule_file_id = null;

    public ?int $schedule_file_page = null;
}
