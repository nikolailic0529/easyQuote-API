<?php

namespace App\DTO\Tasks;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateTaskData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $name;

    public array $content;

    public ?Carbon $expiry_date;

    /**
     * @Constraints\Choice({1,2,3})
     */
    public int $priority;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $users;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $attachments;
}
