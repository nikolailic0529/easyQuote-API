<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Address\Models\Addressable;
use App\Domain\Appointment\Contracts\HasOwnAppointments;
use App\Domain\Appointment\Contracts\LinkedToAppointments;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\ModelHasAppointments;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contactable;
use App\Domain\ContractType\Models\ContractType;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\SalesUnit\Contracts\HasSalesUnit;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Shared\SharingUser\Contacts\HasSharingUserRelations;
use App\Domain\Task\Contracts\LinkedToTasks;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup as WorldwideQuoteAssetsGroupAlias;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\WorldwideQuoteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
 * @property User $user
 * @property Collection<WorldwideQuoteVersion>|WorldwideQuoteVersion[] $versions
 * @property WorldwideQuoteVersion|null $activeVersion
 * @property SalesOrder|null $salesOrder
 * @property ContractType|null $contractType
 * @property Opportunity|null $opportunity
 * @property Collection<Addressable> $referencedAddressPivotsOfPrimaryAccount
 * @property Collection<Contactable> $referencedContactPivotsOfPrimaryAccount
 * @property Collection<Attachment>|Attachment[] $attachments
 * @property bool|null $sales_order_exists
 * @property SalesUnit $salesUnit
 * @property Collection<int, User>|User[] $sharingUsers
 * @property Collection<int, ModelHasSharingUsers>|ModelHasSharingUsers[] $sharingUserRelations
 */
class WorldwideQuote extends Model implements SearchableEntity, LinkedToTasks, LinkedToAppointments, HasOwnAppointments, HasOwnNotes, HasSalesUnit, HasSharingUserRelations
{
    use Uuid;
    use SoftDeletes;
    use HasRelationships;
    use HasFactory;

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

    public function salesUnit(): HasOneThrough
    {
        return $this->hasOneThrough(
            related: SalesUnit::class,
            through: Opportunity::class,
            firstKey: (new Opportunity())->getKeyName(),
            secondKey: (new \App\Domain\SalesUnit\Models\SalesUnit())->getKeyName(),
            localKey: $this->opportunity()->getForeignKeyName(),
            secondLocalKey: (new Opportunity())->salesUnit()->getForeignKeyName(),
        );
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
        return $this->belongsTo(\App\Domain\ContractType\Models\ContractType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, name: 'model', table: (new \App\Domain\Task\Models\ModelHasTasks())->getTable());
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

    public function sharingUserRelations(): HasMany
    {
        $pivot = (new ModelHasSharingUsers());

        return $this->hasMany(ModelHasSharingUsers::class, $pivot->related()->getForeignKeyName());
    }

    public function sharingUsers(): MorphToMany
    {
        $pivot = (new ModelHasSharingUsers());

        return $this->morphToMany(User::class, name: $pivot->related()->getRelationName(), table: $pivot->getTable());
    }
}
