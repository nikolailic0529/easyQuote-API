<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Appointment\Contracts\HasOwnAppointments;
use App\Domain\Appointment\Contracts\LinkedToAppointments;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\ModelHasAppointments;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\Notification\Concerns\NotifiableModel;
use App\Domain\Rescue\Quote\HasContract;
use App\Domain\Rescue\Quote\HasQuoteVersions;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Submittable;
use App\Domain\Task\Contracts\LinkedToTasks;
use App\Domain\Task\Models\ModelHasTasks;
use App\Domain\Task\Models\Task;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Contracts\Multitenantable;
use App\Domain\User\Models\User;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property Customer|null                            $customer
 * @property string|null                              $submitted_at
 * @property string|null                              $assets_migrated_at
 * @property Collection<\App\Domain\Note\Models\Note> $notes
 * @property Collection<Attachment>|Attachment[]      $attachments
 * @property User                                     $user
 */
class Quote extends BaseQuote implements Multitenantable, HasOwner, LinkedToTasks, LinkedToAppointments, HasOwnAppointments, HasOwnNotes
{
    use HasQuoteVersions;
    use HasContract;
    use NotifiableModel;
    use Submittable;
    use Activatable;
    use HasFactory;

    protected static function newFactory(): QuoteFactory
    {
        return QuoteFactory::new();
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            related: \App\Domain\Attachment\Models\Attachment::class,
            name: 'attachable',
            relatedPivotKey: 'attachment_id'
        );
    }

    protected function note(): Attribute
    {
        return Attribute::get(function () {
            return $this->notes
                ->first(static fn (Note $note): bool => $note->getFlag(Note::FROM_ENTITY_WIZARD));
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

    public function markAsMigrated(string $column = 'migrated_at'): void
    {
        static::whereKey($this->getKey())->limit(1)->update([$column => now()]);
    }

    public function markAsNotMigrated(string $column = 'migrated_at'): void
    {
        static::whereKey($this->getKey())->limit(1)->update([$column => null]);
    }

    public function scopeMigrated(Builder $query, string $column = 'migrated_at'): Builder
    {
        return $query->whereNotNull($column);
    }

    public function scopeNotMigrated(Builder $query, string $column = 'migrated_at'): Builder
    {
        return $query->whereNull($column);
    }
}
