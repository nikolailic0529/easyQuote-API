<?php

namespace App\Events\CompanyNote;

use App\Contracts\WithCauserEntity;
use App\Models\CompanyNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CompanyNoteUpdated implements WithCauserEntity
{
    use Dispatchable, SerializesModels;

    public function __construct(protected CompanyNote $note,
                                protected Model       $entity,
                                protected ?Model      $causer = null)
    {
    }

    public function getNote(): CompanyNote
    {
        return $this->note;
    }

    public function getEntity(): Model
    {
        return $this->entity;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }

    public function getNoteText(): string
    {
        return $this->note->getOriginal('text');
    }
}
