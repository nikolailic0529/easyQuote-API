<?php

namespace App\Models\Quote;

use App\Contracts\HasOwnNotes;
use App\Contracts\HasOwnAppointments;
use App\Contracts\LinkedToAppointments;
use App\Contracts\LinkedToTasks;
use App\Contracts\SearchableEntity;
use App\Models\Addressable;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\ModelHasAppointments;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contactable;
use App\Models\ContractType;
use App\Models\ModelHasTasks;
use App\Models\Note\ModelHasNotes;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\SalesOrder;
use App\Models\Task\Task;
use App\Models\User;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup as WorldwideQuoteAssetsGroupAlias;
use App\Traits\Uuid;
use Database\Factories\WorldwideQuoteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null $id
 * @property string|null $active_version_id
 * @property string|null $contract_type_id
 * @property string|null $user_id
 * @property string|null $worldwide_quote_id
 * @property string|null $opportunity_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $submitted_at
 * @property string|null $activated_at
 * @property string|null $quote_number
 * @property int|null $sequence_number
 * @property int|null $status
 * @property string|null $status_reason
 *
 * @property User $user
 * @property Collection<WorldwideQuoteVersion>|WorldwideQuoteVersion[] $versions
 * @property WorldwideQuoteVersion|null $activeVersion
 * @property SalesOrder|null $salesOrder
 * @property ContractType|null $contractType
 * @property Opportunity|null $opportunity
 * @property Collection<Addressable> $referencedAddressPivotsOfPrimaryAccount
 * @property Collection<Contactable> $referencedContactPivotsOfPrimaryAccount
 *
 * @property-read Collection<Attachment>|Attachment[] $attachments
 * @property-read bool|null $sales_order_exists
 */
class WorldwideQuote extends Model implements SearchableEntity, LinkedToTasks, LinkedToAppointments, HasOwnAppointments, HasOwnNotes
{
    use Uuid, SoftDeletes, HasRelationships, HasFactory;

    protected $guarded = [];

    protected static function newFactory(): WorldwideQuoteFactory
    {
        return WorldwideQuoteFactory::new();
    }

    public function assets(): HasManyThrough
    {
        return tap($this->hasManyThrough(
            related: WorldwideQuoteAsset::class,
            through: WorldwideQuoteVersion::class,
            firstKey: 'id',
            secondKey: 'worldwide_quote_id',
            localKey: 'active_version_id'
        ), function (HasManyThrough $relation) {
            $relation
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }

    public function assetsGroups(): HasManyThrough
    {
        return tap($this->hasManyThrough(
            related: WorldwideQuoteAssetsGroupAlias::class,
            through: WorldwideQuoteVersion::class,
            firstKey: 'id',
            secondKey: 'worldwide_quote_version_id',
            localKey: 'active_version_id'
        ), function (HasManyThrough $relation) {
            $relation
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuoteVersion::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorldwideQuoteVersion::class);
    }

    public function referencedAddressPivotsOfPrimaryAccount(): HasManyThrough
    {
        return $this->hasManyDeep(
            Addressable::class,
            [
                Opportunity::class, Company::class,
            ],
            [
                'id',
                'id',
                'addressable_id',
            ],
            [
                'opportunity_id',
                'primary_account_id',
                null,
            ]
        );
    }


    public function referencedContactPivotsOfPrimaryAccount(): HasManyThrough
    {
        return $this->hasManyDeep(
            Contactable::class,
            [
                Opportunity::class, Company::class,
            ],
            [
                'id',
                'id',
                'contactable_id',
            ],
            [
                'opportunity_id',
                'primary_account_id',
                null,
            ]
        );
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, name: 'model', table: (new ModelHasTasks())->getTable());
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            related: Attachment::class,
            name: 'attachable',
            relatedPivotKey: 'attachment_id'
        );
    }

    public function toSearchArray(): array
    {
        return [
            'rfq_number' => $this->quote_number,
            'customer_name' => $this->opportunity->primaryAccount?->name,
            'end_user_name' => $this->opportunity->endUser?->name,
            'company_name' => $this->activeVersion->company?->name,
            'contract_type_name' => $this->contractType->type_short_name,
            'valid_until_date' => $this->opportunity->opportunity_closing_date,
            'support_start_date' => $this->opportunity->opportunity_start_date,
            'support_end_date' => $this->opportunity->opportunity_end_date,
            'created_at' => $this->created_at?->toDateString(),
        ];
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, foreignPivotKey: 'quote_id');
    }

    public function ownAppointments(): MorphToMany
    {
        return $this->morphToMany(Appointment::class, name: 'model', table: (new ModelHasAppointments())->getTable());
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
}
