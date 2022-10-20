<?php

namespace App\Services\Note;

use App\Contracts\CauserAware;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Models\CreateNoteInput;
use App\Integrations\Pipeliner\Models\NoteEntity;
use App\Integrations\Pipeliner\Models\UpdateNoteInput;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Services\Pipeliner\PipelinerClientEntityToUserProjector;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class NoteDataMapper implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly PipelinerClientEntityToUserProjector $clientProjector,
    )
    {
    }

    public function mapFromNoteEntity(NoteEntity $entity): Note
    {
        return tap(new Note(), function (Note $note) use ($entity): void {
            $note->{$note->getKeyName()} = (string)Uuid::generate(4);
            $note->pl_reference = $entity->id;

            $note->owner()->associate(($this->clientProjector)($entity->owner));

            $note->note = $entity->note;

            if (null !== $entity->accountId) {
                $note->setRelation('companiesHaveNote', Company::query()->where('pl_reference', $entity->accountId)->get());
            }

            if (null !== $entity->contactId) {
                $note->setRelation('contactsHaveNote', Contact::query()->where('pl_reference', $entity->contactId)->get());
            }

            if (null !== $entity->leadOpptyId) {
                $note->setRelation('opportunitiesHaveNote', Opportunity::query()->where('pl_reference', $entity->leadOpptyId)->get());
            }
        });
    }

    public function mergeAttributesFrom(Note $note, Note $another): void
    {
        $toBeMergedAttributes = [
            'note',
        ];

        foreach ($toBeMergedAttributes as $attribute) {
            $note->$attribute = $another->$attribute;
        }

        $toBeMergedManyToManyRelations = [
            'companiesHaveNote',
            'contactsHaveNote',
            'opportunitiesHaveNote',
        ];

        foreach ($toBeMergedManyToManyRelations as $relation) {
            /** @var Collection $relatedOriginal */
            $relatedOriginal = $note->$relation;

            /** @var Collection $relatedChanged */
            $relatedChanged = $another->$relation;

            $relatedOriginal->push(...$relatedChanged);
        }
    }

    public function mapPipelinerCreateNoteInput(Note $note): CreateNoteInput
    {
        $attributes = [];

        $attributes['ownerId'] = (string)$note->owner?->pl_reference;
        $attributes['note'] = (string)$note->note;

        $attributes['accountId'] = $note->companiesHaveNote[0]?->pl_reference ?? InputValueEnum::Miss;
        $attributes['contactId'] = $note->contactsHaveNote[0]?->pl_reference ?? InputValueEnum::Miss;
        $attributes['leadOpptyId'] = $note->opportunitiesHaveNote[0]?->pl_reference ?? InputValueEnum::Miss;

        return new CreateNoteInput(...$attributes);
    }

    public function mapPipelinerUpdateNoteInput(Note $note, NoteEntity $entity): UpdateNoteInput
    {
        $attributes = [];

        $attributes['id'] = $entity->id;
        $attributes['ownerId'] = $note->owner?->pl_reference ?? InputValueEnum::Miss;

        $attributes['note'] = (string)$note->note;

        $attributes['accountId'] = $note->companiesHaveNote[0]?->pl_reference ?? InputValueEnum::Miss;
        $attributes['contactId'] = $note->contactsHaveNote[0]?->pl_reference ?? InputValueEnum::Miss;
        $attributes['leadOpptyId'] = $note->opportunitiesHaveNote[0]?->pl_reference ?? InputValueEnum::Miss;

        $comparableFieldsMap = [
            'ownerId' => $entity->owner->id,
            'note' => $entity->note,
            'accountId' => $entity->accountId,
            'contactId' => $entity->contactId,
            'leadOpptyId' => $entity->leadOpptyId,
        ];

        foreach ($comparableFieldsMap as $key => $value) {
            if ($value === $attributes[$key]) {
                $attributes[$key] = InputValueEnum::Miss;
            }
        }

        return new UpdateNoteInput(...$attributes);
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}