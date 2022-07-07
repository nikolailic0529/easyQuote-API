<?php

namespace App\DTO\Appointment;

use App\Enum\AppointmentTypeEnum;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateAppointmentData extends DataTransferObject
{
    public AppointmentTypeEnum $activity_type;

    #[Constraints\NotBlank]
    public string $subject;

    public string $description;

    public ?string $location;

    #[Constraints\DateTime]
    public \DateTimeImmutable $start_date;

    #[Constraints\DateTime]
    #[Constraints\GreaterThanOrEqual(propertyPath: "start_date")]
    public \DateTimeImmutable $end_date;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $invitee_user_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $invitee_contact_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $company_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $opportunity_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $contact_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $user_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $rescue_quote_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $worldwide_quote_relations;

    /** @var string[]|null */
    #[Constraints\All(constraints: [new Constraints\Uuid()])]
    public ?array $attachment_relations;

    public ?CreateAppointmentReminderData $reminder;
}