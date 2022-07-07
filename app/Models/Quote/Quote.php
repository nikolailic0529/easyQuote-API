<?php

namespace App\Models\Quote;

use App\Contracts\HasOwnNotes;
use App\Contracts\HasOwnAppointments;
use App\Contracts\HasOwner;
use App\Contracts\LinkedToAppointments;
use App\Contracts\LinkedToTasks;
use App\Contracts\Multitenantable;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\ModelHasAppointments;
use App\Models\Attachment;
use App\Models\Customer\Customer;
use App\Models\ModelHasTasks;
use App\Models\Note\ModelHasNotes;
use App\Models\Note\Note;
use App\Models\Task\Task;
use App\Models\User;
use App\Traits\{Activatable, Migratable, NotifiableModel, Quote\HasContract, Quote\HasQuoteVersions, Submittable};
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property Customer|null $customer
 * @property string|null $submitted_at
 * @property string|null $assets_migrated_at
 *
 * @property-read Collection<Note> $notes
 * @property-read Collection<Attachment>|Attachment[] $attachments
 */
class Quote extends BaseQuote implements Multitenantable, HasOwner, LinkedToTasks, LinkedToAppointments, HasOwnAppointments, HasOwnNotes
{
    use HasQuoteVersions, HasContract, NotifiableModel, Submittable, Activatable, Migratable, HasFactory;

    protected static function newFactory(): QuoteFactory
    {
        return QuoteFactory::new();
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            related: Attachment::class,
            name: 'attachable',
            relatedPivotKey: 'attachment_id'
        );
    }

    protected function note(): Attribute
    {
        return Attribute::get(function () {
            return $this->notes
                ->first(static fn(Note $note): bool => $note->getFlag(Note::FROM_ENTITY_WIZARD));
        });
    }

    public function notes(): MorphToMany
    {
        return $this->morphToMany(
            related: Note::class,
            name: 'model',
            table: (new ModelHasNotes())->getTable(),
            relatedPivotKey: 'note_id',
        )->using(ModelHasNotes::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, table: 'appointment_rescue_quote', foreignPivotKey: 'quote_id');
    }

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, name: 'model', table: (new ModelHasTasks())->getTable());
    }

    public function ownAppointments(): MorphToMany
    {
        return $this->morphToMany(Appointment::class, name: 'model', table: (new ModelHasAppointments())->getTable());
    }
}
