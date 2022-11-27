<?php

namespace App\Services\Appointment;

use App\Contracts\CauserAware;
use App\Enum\AppointmentTypeEnum;
use App\Enum\InviteeResponse;
use App\Enum\InviteeType;
use App\Enum\ReminderStatus;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\InviteeResponseEnum;
use App\Integrations\Pipeliner\Enum\InviteeTypeEnum;
use App\Integrations\Pipeliner\Enum\ReminderStatusEnum;
use App\Integrations\Pipeliner\Models\AppointmentEntity;
use App\Integrations\Pipeliner\Models\CreateActivityAccountRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityAccountRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateActivityClientRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityClientRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateActivityContactRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityContactRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateActivityLeadOpptyRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityLeadOpptyRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput;
use App\Integrations\Pipeliner\Models\CreateAppointmentContactInviteesRelationNoAppointmentBackrefInputCollection;
use App\Integrations\Pipeliner\Models\CreateAppointmentInput;
use App\Integrations\Pipeliner\Models\CreateAppointmentReminderInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInputCollection;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use App\Integrations\Pipeliner\Models\UpdateAppointmentInput;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentContactInvitee;
use App\Models\Appointment\AppointmentReminder;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\SalesUnit;
use App\Models\User;
use App\Services\Pipeliner\CachedAppointmentTypeResolver;
use App\Services\Pipeliner\PipelinerClientEntityToUserProjector;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Webpatser\Uuid\Uuid;

class AppointmentDataMapper implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected CachedAppointmentTypeResolver $pipelinerAppointmentTypeResolver,
        protected PipelinerClientEntityToUserProjector $clientProjector
    ) {
    }

    public function mapFromAppointmentEntity(AppointmentEntity $entity): Appointment
    {
        return tap(new Appointment(), function (Appointment $appointment) use ($entity): void {
            $appointment->{$appointment->getKeyName()} = (string) Uuid::generate(4);
            $appointment->pl_reference = $entity->id;
            $appointment->activity_type = AppointmentTypeEnum::tryFrom($entity->activityType->name) ?? AppointmentTypeEnum::Appointment;
            $appointment->subject = $entity->subject;
            $appointment->description = $entity->description;

            $appointment->start_date = Carbon::instance($entity->startDate);
            $appointment->end_date = Carbon::instance($entity->endDate);
            $appointment->location = $entity->location;

            if (null !== $entity->unit) {
                $appointment->salesUnit()->associate(
                    SalesUnit::query()->where('unit_name', $entity->unit->name)->first()
                );
            }

            $reminder = isset($entity->reminder)
                ? tap(new AppointmentReminder(), static function (AppointmentReminder $reminder) use (
                    $appointment,
                    $entity
                ) {
                    $reminder->appointment()->associate($appointment);

                    $reminder->start_date_offset = $entity->reminder->endDateOffset;
                    $reminder->snooze_date = isset($entity->reminder->snoozeDate)
                        ? Carbon::instance($entity->reminder->snoozeDate)
                        : null;
                    $reminder->status = match ($entity->reminder->status) {
                        ReminderStatusEnum::Snoozed => ReminderStatus::Snoozed,
                        ReminderStatusEnum::Scheduled => ReminderStatus::Scheduled,
                        ReminderStatusEnum::Dismissed => ReminderStatus::Dismissed,
                    };
                })
                : null;

            $appointment->setRelation('reminder', $reminder);

            $appointment->owner()->associate(($this->clientProjector)($entity->owner));

            $appointment->setRelation('companies', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->accountRelations as $accountRelation) {

                    $account = Company::query()->where('pl_reference', $accountRelation->accountId)->first();

                    if (null !== $account) {
                        $relations[] = $account;
                    }
                }

                return $relations;
            }));

            $appointment->setRelation('contacts', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->contactRelations as $contactRelation) {

                    $contact = Contact::query()->where('pl_reference', $contactRelation->contactId)->first();

                    if (null !== $contact) {
                        $relations[] = $contact;
                    }
                }

                return $relations;
            }));

            $appointment->setRelation('inviteesContacts', value(function () use ($appointment, $entity): Collection {
                $relations = new Collection();

                foreach ($entity->inviteesContacts as $contactRelation) {
                    $relations[] = tap(new AppointmentContactInvitee(),
                        static function (AppointmentContactInvitee $invitee) use (
                            $appointment,
                            $contactRelation
                        ): void {
                            $invitee->appointment()->associate($appointment);
                            $invitee->pl_reference = $contactRelation->id;

                            if (null !== $contactRelation->contactId) {
                                $invitee->contact()->associate(
                                    Contact::query()->where('pl_reference', $contactRelation->contactId)->first()
                                );
                            }

                            $invitee->email = $contactRelation->email;
                            $invitee->first_name = $contactRelation->firstName;
                            $invitee->last_name = $contactRelation->lastName;
                            $invitee->invitee_type = match ($contactRelation->inviteeType) {
                                InviteeTypeEnum::Standard => InviteeType::Standard,
                                InviteeTypeEnum::Scheduled => InviteeType::Scheduled,
                            };
                            $invitee->response = match ($contactRelation->response) {
                                InviteeResponseEnum::NoResponse => InviteeResponse::NoResponse,
                                InviteeResponseEnum::Accepted => InviteeResponse::Accepted,
                                InviteeResponseEnum::Rejected => InviteeResponse::Rejected,
                            };
                        });
                }

                return $relations;
            }));

            $appointment->setRelation('inviteesUsers', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->inviteesClients as $contactRelation) {

                    $contact = User::query()->where('pl_reference', $contactRelation->clientId)->first();

                    if (null !== $contact) {
                        $relations[] = $contact;
                    }
                }

                return $relations;
            }));

            $appointment->setRelation('opportunities', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->opportunityRelations as $opptyRelation) {

                    $oppty = Opportunity::query()->where('pl_reference', $opptyRelation->leadOpptyId)->first();

                    if (null !== $oppty) {
                        $relations[] = $oppty;
                    }
                }

                return $relations;
            }));

            $appointment->setRelation('opportunitiesHaveAppointment', $appointment->opportunities);
            $appointment->setRelation('companiesHaveAppointment', $appointment->companies);
        });
    }

    public function mergeAttributesFrom(Appointment $appointment, Appointment $another): void
    {
        $toBeMergedAttributes = [
            'activity_type',
            'subject',
            'description',
            'start_date',
            'end_date',
            'location',
        ];

        foreach ($toBeMergedAttributes as $attribute) {
            $appointment->$attribute = $another->$attribute;
        }

        $toBeMergedHasOneRelations = [
            'reminder',
        ];

        foreach ($toBeMergedHasOneRelations as $relation) {
            /** @var Model&SoftDeletes|null $relatedOriginal */
            $relatedOriginal = $appointment->$relation;

            /** @var Model&SoftDeletes|null $relatedOriginal */
            $relatedChanged = $another->$relation;

            if (null === $relatedChanged && null !== $relatedOriginal) {
                $relatedOriginal->{$relatedOriginal->getDeletedAtColumn()} = $relatedOriginal->freshTimestamp();
            } elseif (null !== $another->$relation) {
                $appointment->setRelation($relation, $relatedChanged->replicate()->task()->associate($appointment));
            }
        }

        $toBeMergedBelongsToRelations = [
            'salesUnit',
        ];

        foreach ($toBeMergedBelongsToRelations as $relation) {
            if (null !== $another->$relation) {
                $appointment->$relation()->associate($another->$relation);
            }
        }

        $toBeMergedManyToManyRelations = [
            'companies',
            'contacts',
            'inviteesUsers',
            'opportunities',
            'opportunitiesHaveAppointment',
            'companiesHaveAppointment',
        ];

        foreach ($toBeMergedManyToManyRelations as $relation) {
            /** @var Collection $relatedOriginal */
            $relatedOriginal = $appointment->$relation;

            /** @var Collection $relatedChanged */
            $relatedChanged = $another->$relation;

            $appointment->setRelation($relation, $relatedOriginal->push(...$relatedChanged)->unique()->values());
        }

        $toBeMergedOneToManyRelations = [
            'inviteesContacts',
        ];

        foreach ($toBeMergedOneToManyRelations as $relation) {
            /** @var Collection $relatedOriginal */
            $relatedOriginal = $appointment->$relation;

            /** @var Collection $relatedChanged */
            $relatedChanged = $another->$relation;

            $relatedChanged = $relatedChanged->map(static function (Model $model) use ($appointment): Model {
                return $model->replicate()->appointment()->associate($appointment);
            });

            $appointment->setRelation($relation, $relatedOriginal->push(...$relatedChanged)->unique()->values());
        }
    }

    public function mapPipelinerCreateAppointmentInput(Appointment $appointment): CreateAppointmentInput
    {
        $attributes = [];

        if (null !== $appointment->owner) {
            $attributes['ownerId'] = (string) $appointment->owner->pl_reference;
        }

        $attributes['activityTypeId'] = ($this->pipelinerAppointmentTypeResolver)($appointment->activity_type->value)?->id ?? InputValueEnum::Miss;
        $attributes['subject'] = $appointment->subject;
        $attributes['description'] = $appointment->description;
        $attributes['startDate'] = Carbon::instance($appointment->start_date)->toDateTimeImmutable();
        $attributes['endDate'] = Carbon::instance($appointment->end_date)->toDateTimeImmutable();
        $attributes['location'] = $appointment->location;
        $attributes['accountRelations'] = $appointment
            ->companies
            ->merge($appointment->companiesHaveAppointment)
            ->whereNotNull('pl_reference')
            ->map(static function (Company $company): CreateActivityAccountRelationInput {
                return new CreateActivityAccountRelationInput($company->pl_reference);
            })
            ->pipe(static function (BaseCollection $collection): CreateActivityAccountRelationInputCollection {
                return new CreateActivityAccountRelationInputCollection(...$collection->all());
            });
        $attributes['contactRelations'] = $appointment->contacts->whereNotNull('pl_reference')
            ->map(static function (Contact $contact): CreateActivityContactRelationInput {
                return new CreateActivityContactRelationInput($contact->pl_reference);
            })
            ->pipe(static function (BaseCollection $collection): CreateActivityContactRelationInputCollection {
                return new CreateActivityContactRelationInputCollection(...$collection->all());
            });
        $attributes['opportunityRelations'] = $appointment->opportunities
            ->merge($appointment->opportunitiesHaveAppointment)
            ->whereNotNull('pl_reference')
            ->map(static function (Opportunity $opportunity): CreateActivityLeadOpptyRelationInput {
                return new CreateActivityLeadOpptyRelationInput($opportunity->pl_reference);
            })
            ->pipe(static function (BaseCollection $collection): CreateActivityLeadOpptyRelationInputCollection {
                return new CreateActivityLeadOpptyRelationInputCollection(...$collection->all());
            });
//        $attributes['inviteesClients'] = $appointment->inviteesUsers->whereNotNull('pl_reference')
//            ->map(static function (User $user): CreateActivityClientRelationInput {
//                return new CreateActivityClientRelationInput($user->pl_reference);
//            })
//            ->pipe(static function (BaseCollection $collection): CreateActivityClientRelationInputCollection {
//                return new CreateActivityClientRelationInputCollection(...$collection->all());
//            });
//        $attributes['inviteesContacts'] = $appointment->inviteesContacts
//            ->whereNotNull('pl_reference')
//            ->unique('pl_reference')
//            ->values()
//            ->map(static function (AppointmentContactInvitee $contact
//            ): CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput {
//                return new CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput(
//                    contactId: $contact->pl_reference,
//                    email: $contact->email ?? '',
//                );
//            })
//            ->pipe(static function (BaseCollection $collection
//            ): CreateAppointmentContactInviteesRelationNoAppointmentBackrefInputCollection {
//                return new CreateAppointmentContactInviteesRelationNoAppointmentBackrefInputCollection(...
//                    $collection->all());
//            });
        $attributes['reminder'] = value(static function () use ($appointment
        ): CreateAppointmentReminderInput|InputValueEnum {
            if (null === $appointment->reminder) {
                return InputValueEnum::Miss;
            }

            return new CreateAppointmentReminderInput(
                ownerId: $appointment->reminder->owner?->pl_reference ?? $appointment->owner->pl_reference ?? '',
                endDateOffset: $appointment->reminder->start_date_offset,
                snoozeDate: isset($appointment->reminder->snooze_date)
                    ? Carbon::instance($appointment->reminder->snooze_date)->toDateTimeImmutable()
                    : InputValueEnum::Miss,
                status: match ($appointment->reminder->status) {
                    ReminderStatus::Scheduled => ReminderStatusEnum::Scheduled,
                    ReminderStatus::Snoozed => ReminderStatusEnum::Snoozed,
                    ReminderStatus::Dismissed => ReminderStatusEnum::Dismissed,
                    default => InputValueEnum::Miss,
                },
            );
        });
        $attributes['unitId'] = $appointment->salesUnit?->pl_reference ?? InputValueEnum::Miss;
        $attributes['documents'] = $appointment->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn(): InputValueEnum => InputValueEnum::Miss);

        return new CreateAppointmentInput(
            ...$attributes
        );
    }

    public function mapPipelinerUpdateAppointmentInput(
        Appointment $appointment,
        AppointmentEntity $entity
    ): UpdateAppointmentInput {
        $attributes = [];

        $attributes['id'] = $entity->id;
        $attributes['unitId'] = value(static function (
            ?SalesUnit $unit,
            ?SalesUnitEntity $entity
        ): InputValueEnum|string {
            if (null === $unit || $unit->pl_reference === $entity?->id) {
                return InputValueEnum::Miss;
            }

            return $unit->pl_reference ?? InputValueEnum::Miss;
        }, $appointment->salesUnit, $entity->unit);
        $attributes['activityTypeId'] = ($this->pipelinerAppointmentTypeResolver)($appointment->activity_type->value)?->id ?? InputValueEnum::Miss;
        $attributes['subject'] = $appointment->subject;
        $attributes['description'] = $appointment->description;
        $attributes['startDate'] = Carbon::instance($appointment->start_date)->toDateTimeImmutable();
        $attributes['endDate'] = Carbon::instance($appointment->end_date)->toDateTimeImmutable();
        $attributes['location'] = $appointment->location;
        $attributes['accountRelations'] = $appointment
            ->companies
            ->merge($appointment->companiesHaveAppointment)
            ->whereNotNull('pl_reference')
            ->map(static function (Company $company): CreateActivityAccountRelationInput {
                return new CreateActivityAccountRelationInput($company->pl_reference);
            })
            ->pipe(static function (BaseCollection $collection): CreateActivityAccountRelationInputCollection {
                return new CreateActivityAccountRelationInputCollection(...$collection->all());
            });
        $attributes['contactRelations'] = $appointment->contacts
            ->whereNotNull('pl_reference')
            ->unique('pl_reference')
            ->values()
            ->map(static function (Contact $contact): CreateActivityContactRelationInput {
                return new CreateActivityContactRelationInput($contact->pl_reference);
            })
            ->pipe(static function (BaseCollection $collection): CreateActivityContactRelationInputCollection {
                return new CreateActivityContactRelationInputCollection(...$collection->all());
            });
        $attributes['opportunityRelations'] = $appointment->opportunities
            ->merge($appointment->opportunitiesHaveAppointment)
            ->whereNotNull('pl_reference')
            ->unique('pl_reference')
            ->values()
            ->map(static function (Opportunity $opportunity): CreateActivityLeadOpptyRelationInput {
                return new CreateActivityLeadOpptyRelationInput($opportunity->pl_reference);
            })
            ->pipe(static function (BaseCollection $collection): CreateActivityLeadOpptyRelationInputCollection {
                return new CreateActivityLeadOpptyRelationInputCollection(...$collection->all());
            });
//        $attributes['inviteesClients'] = $appointment->inviteesUsers
//            ->whereNotNull('pl_reference')
//            ->unique('pl_reference')
//            ->values()
//            ->map(static function (User $user): CreateActivityClientRelationInput {
//                return new CreateActivityClientRelationInput($user->pl_reference);
//            })
//            ->pipe(static function (BaseCollection $collection): CreateActivityClientRelationInputCollection {
//                return new CreateActivityClientRelationInputCollection(...$collection->all());
//            });
//        $attributes['inviteesContacts'] = $appointment->inviteesContacts
//            ->whereNotNull('pl_reference')
//            ->unique('pl_reference')
//            ->values()
//            ->map(static function (AppointmentContactInvitee $contact
//            ): CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput {
//                return new CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput(
//                    contactId: $contact->pl_reference,
//                    email: $contact->email ?? '',
//                );
//            })
//            ->pipe(static function (BaseCollection $collection
//            ): CreateAppointmentContactInviteesRelationNoAppointmentBackrefInputCollection {
//                return new CreateAppointmentContactInviteesRelationNoAppointmentBackrefInputCollection(...
//                    $collection->all());
//            });
        $attributes['documents'] = $appointment->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn(): InputValueEnum => InputValueEnum::Miss);
//        $attributes['reminder'] = value(static function () use ($appointment): CreateAppointmentReminderInput|InputValueEnum {
//            if (null === $appointment->reminder) {
//                return InputValueEnum::Miss;
//            }
//
//            return new CreateAppointmentReminderInput(
//                ownerId: $appointment->reminder->owner?->pl_reference ?? '',
//                endDateOffset: $appointment->reminder->start_date_offset,
//                snoozeDate: isset($appointment->reminder->snooze_date)
//                    ? Carbon::instance($appointment->reminder->snooze_date)
//                    : InputValueEnum::Miss,
//                status: match ($appointment->reminder->status) {
//                    ReminderStatus::Scheduled => ReminderStatusEnum::Scheduled,
//                    ReminderStatus::Snoozed => ReminderStatusEnum::Snoozed,
//                    ReminderStatus::Dismissed => ReminderStatusEnum::Dismissed,
//                    default => InputValueEnum::Miss,
//                },
//            );
//        });

        return new UpdateAppointmentInput(...$attributes);
    }

    public function cloneAppointment(Appointment $appointment): Appointment
    {
        return tap(new Appointment(), function (Appointment $old) use ($appointment): void {
            $old->setRawAttributes($appointment->getRawOriginal());

            collect([
                'salesUnit',
                'inviteesUsers',
                'inviteesContacts',
                'reminder',
                'companies',
                'opportunities',
                'contacts',
                'users',
            ])->each(static function (string $relation) use ($old, $appointment): void {
                $old->setRelation($relation, $appointment->$relation);
            });
        });
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}