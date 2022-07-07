<?php

namespace App\DTO\Tasks;

use App\Enum\Priority;
use App\Enum\TaskTypeEnum;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateTaskData extends DataTransferObject
{
    public TaskTypeEnum $activity_type;

    #[Constraints\NotBlank]
    public string $name;

    public array $content;

    public ?Carbon $expiry_date;

    public Priority $priority;

    public ?CreateTaskReminderData $reminder;

    public ?CreateTaskRecurrenceData $recurrence;

    #[Constraints\All([new Constraints\Uuid])]
    public array $users;

    #[Constraints\All([new Constraints\Uuid])]
    public array $attachments;
}
