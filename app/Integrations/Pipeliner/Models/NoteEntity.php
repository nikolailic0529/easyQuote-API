<?php

namespace App\Integrations\Pipeliner\Models;

class NoteEntity
{
    public function __construct(public readonly string             $id,
                                public readonly ClientEntity       $owner,
                                public readonly ?string            $accountId,
                                public readonly ?string            $contactId,
                                public readonly ?string            $leadOpptyId,
                                public readonly ?string            $projectId,
                                public readonly string             $note,
                                public readonly \DateTimeImmutable $created,
                                public readonly \DateTimeImmutable $modified,
                                public readonly int                $revision)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            owner: ClientEntity::fromArray($array['owner']),
            accountId: $array['accountId'],
            contactId: $array['contactId'],
            leadOpptyId: $array['leadOpptyId'],
            projectId: $array['projectId'],
            note: $array['note'],
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }
}