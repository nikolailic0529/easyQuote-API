<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Contracts\HasOwnAppointments;
use App\Domain\Appointment\Contracts\LinkedToAppointments;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\ModelHasAppointments;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\ImportedCompany;
use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Models\ImportedContact;
use App\Domain\ContractType\Models\ContractType;
use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Models\PipelineStage;
use App\Domain\Pipeliner\Models\PipelinerSyncError;
use App\Domain\Pipeliner\Models\PipelinerSyncStrategyLog;
use App\Domain\SalesUnit\Contracts\HasSalesUnit;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Shared\Eloquent\Concerns\HasTimestamps;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\SharingUser\Contacts\HasSharingUserRelations;
use App\Domain\Task\Contracts\LinkedToTasks;
use App\Domain\Task\Models\ModelHasTasks;
use App\Domain\Task\Models\Task;
use App\Domain\User\Contracts\AssignableToUsers;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Builders\OpportunityBuilder;
use App\Domain\Worldwide\Enum\OpportunityStatus;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Carbon\CarbonInterface;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class Opportunity.
 *
 * @property string|null                                                                                           $pl_reference
 * @property string|null                                                                                           $user_id
 * @property string|null                                                                                           $pipeline_id
 * @property string|null                                                                                           $pipeline_stage_id
 * @property string|null                                                                                           $contract_type_id
 * @property string|null                                                                                           $primary_account_id
 * @property string|null                                                                                           $end_user_id
 * @property bool|null                                                                                             $are_end_user_addresses_available
 * @property bool|null                                                                                             $are_end_user_contacts_available
 * @property string|null                                                                                           $primary_account_contact_id
 * @property string|null                                                                                           $imported_primary_account_id
 * @property string|null $imported_primary_account_contact_id
 * @property string|null $account_manager_id
 * @property string|null $project_name
 * @property string|null $nature_of_service
 * @property string|null $renewal_month
 * @property int|null $renewal_year
 * @property string|null $customer_status
 * @property string|null $end_user_name
 * @property string|null $hardware_status
 * @property string|null $region_name
 * @property CarbonInterface|null $opportunity_start_date
 * @property bool|null $is_opportunity_start_date_assumed
 * @property CarbonInterface|null $opportunity_end_date
 * @property bool|null $is_opportunity_end_date_assumed
 * @property CarbonInterface|null $opportunity_closing_date
 * @property int|null $contract_duration_months
 * @property bool|null $is_contract_duration_checked
 * @property string|null $customer_order_date
 * @property string|null $purchase_order_date
 * @property string|null $supplier_order_date
 * @property string|null $supplier_order_transaction_date
 * @property string|null $supplier_order_confirmation_date
 * @property string|null $expected_order_date
 * @property float|null $opportunity_amount
 * @property string|null $opportunity_amount_currency_code
 * @property float|null                                                                                            $purchase_price
 * @property string|null                                                                                           $purchase_price_currency_code
 * @property float|null                                                                                            $list_price
 * @property string|null                                                                                           $list_price_currency_code
 * @property float|null                                                                                            $estimated_upsell_amount
 * @property string|null                                                                                           $estimated_upsell_amount_currency_code
 * @property float|null                                                                                            $margin_value
 * @property float|null                                                                                            $base_opportunity_amount
 * @property float|null                                                                                            $base_list_price
 * @property float|null                                                                                            $base_purchase_price
 * @property string|null                                                                                           $account_manager_name
 * @property string|null                                                                                           $service_level_agreement_id
 * @property string|null                                                                                           $sale_unit_name
 * @property string|null                                                                                           $competition_name
 * @property string|null                                                                                           $drop_in                               // ?
 * @property string|null                                                                                           $lead_source_name
 * @property bool|null                                                                                             $has_higher_sla
 * @property bool|null                                                                                             $is_multi_year
 * @property bool|null                                                                                             $has_additional_hardware
 * @property bool|null                                                                                             $has_service_credits
 * @property string|null                                                                                           $remarks
 * @property string|null                                                                                           $notes                                 // Description
 * @property string|null                                                                                           $personal_rating
 * @property int|null                                                                                              $ranking
 * @property string|null                                                                                           $campaign_name
 * @property int|null                                                                                              $order_in_pipeline_stage
 * @property string|null                                                                                           $sale_action_name                      // {"Preparation", "Special Bid Required", "Quote Ready", "Customer Contact", "Customer Order OK", "PO Placed", "Processed on BC", "Closed"}
 * @property int|null                                                                                              $flags
 * @property OpportunityStatus|null                                                                                $status
 * @property string|null                                                                                           $status_reason
 * @property Carbon|null                                                                                           $created_at
 * @property Carbon|null                                                                                           $updated_at
 * @property Carbon|null                                                                                           $archived_at
 * @property SalesUnit|null                                                                                        $salesUnit
 * @property \App\Domain\Pipeline\Models\Pipeline|null                                                             $pipeline
 * @property \App\Domain\Pipeline\Models\PipelineStage|null                                                        $pipelineStage
 * @property OpportunityRecurrence|null                                                                            $recurrence
 * @property ContractType|null                                                                                     $contractType
 * @property Company|null                                                                                          $primaryAccount
 * @property Company|null                                                                                          $endUser
 * @property ImportedCompany|null                                                                                  $importedPrimaryAccount
 * @property Contact|null $primaryAccountContact
 * @property \App\Domain\Contact\Models\ImportedContact|null $importedPrimaryAccountContact
 * @property User|null $accountManager
 * @property Collection<OpportunitySupplier>|OpportunitySupplier[] $opportunitySuppliers
 * @property Collection<\App\Domain\Worldwide\Models\WorldwideQuote>|\App\Domain\Worldwide\Models\WorldwideQuote[] $worldwideQuotes
 * @property Collection<int, \App\Domain\Task\Models\Task>|\App\Domain\Task\Models\Task[] $tasks
 * @property bool|null $quotes_exist
 * @property Collection<int,Attachment>|Attachment[] $attachments
 * @property OpportunityValidationResult|null $validationResult
 * @property Collection<int, \App\Domain\Appointment\Models\Appointment> $ownAppointments
 * @property Collection<int, User>|User[] $sharingUsers
 * @property Collection<int, ModelHasSharingUsers>|ModelHasSharingUsers[] $sharingUserRelations
 */
class Opportunity extends Model implements SearchableEntity, HasOwner, LinkedToAppointments, HasOwnAppointments, LinkedToTasks, HasOwnNotes, ProvidesIdForHumans, HasSalesUnit, HasSharingUserRelations
{
    use Uuid;
    use SoftDeletes;
    use HasRelationships;
    use HasFactory;
    use HasTimestamps;

    const SYNC_PROTECTED = 1 << 2;

    protected $guarded = [];

    protected $casts = [
        'status' => OpportunityStatus::class,
//        'opportunity_start_date' => 'date',
//        'opportunity_end_date' => 'date',
//        'opportunity_closing_date' => 'date',
        'is_opportunity_start_date_assumed' => 'boolean',
        'is_opportunity_end_date_assumed' => 'boolean',
        'is_contract_duration_checked' => 'boolean',
        'has_higher_sla' => 'boolean',
        'has_additional_hardware' => 'boolean',
        'is_multi_year' => 'boolean',
        'has_service_credits' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    public static function query(): OpportunityBuilder
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::query();
    }

    public function newQuery(): OpportunityBuilder
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::newQuery();
    }

    public function newEloquentBuilder($query): OpportunityBuilder
    {
        return new OpportunityBuilder($query);
    }

    protected static function newFactory(): OpportunityFactory
    {
        return OpportunityFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(OpportunityRecurrence::class)->latestOfMany();
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function primaryAccount(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function endUser(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function importedPrimaryAccount(): BelongsTo
    {
        return $this->belongsTo(ImportedCompany::class);
    }

    public function primaryAccountContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function importedPrimaryAccountContact(): BelongsTo
    {
        return $this->belongsTo(ImportedContact::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunitySuppliers(): HasMany
    {
        return $this->hasMany(OpportunitySupplier::class)->oldest('entity_order');
    }

//    public function worldwideQuote(): HasOne
//    {
//        return $this->hasOne(WorldwideQuote::class);
//    }

    public function worldwideQuotes(): HasMany
    {
        return $this->hasMany(WorldwideQuote::class);
    }

    public function countries(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->primaryAccount(), (new Company())->addresses(), (new Address())->country());
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class);
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

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, name: 'model', table: (new ModelHasTasks())->getTable());
    }

    public function toSearchArray(): array
    {
        return [
            'primary_account_name' => $this->primaryAccount?->name,
            'end_user_name' => $this->endUser?->name,
            'project_name' => $this->project_name,
            'contract_type' => $this->contractType->type_short_name,
            'opportunity_start_date' => $this->opportunity_start_date,
            'opportunity_end_date' => $this->opportunity_end_date,
            'opportunity_amount' => (string) $this->opportunity_amount,
        ];
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function ownAppointments(): MorphToMany
    {
        return $this->morphToMany(Appointment::class, name: 'model', table: (new ModelHasAppointments())->getTable());
    }

    public function sharingUsers(): MorphToMany
    {
        $pivot = (new ModelHasSharingUsers());

        return $this->morphToMany(User::class, name: $pivot->related()->getRelationName(), table: $pivot->getTable());
    }

    public function sharingUserRelations(): HasMany
    {
        $pivot = (new ModelHasSharingUsers());

        return $this->hasMany(ModelHasSharingUsers::class, $pivot->related()->getForeignKeyName());
    }

    public function pipelinerSyncLogs(): MorphMany
    {
        return $this->morphMany(related: PipelinerSyncStrategyLog::class, name: 'model');
    }

    public function latestPipelinerSyncLog(): MorphOne
    {
        return $this->morphOne(related: PipelinerSyncStrategyLog::class, name: 'model')->latestOfMany('updated_at');
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            related: Attachment::class,
            name: 'attachable',
            relatedPivotKey: 'attachment_id'
        );
    }

    public function validationResult(): HasOne
    {
        return $this->hasOne(OpportunityValidationResult::class)->withDefault(static function (OpportunityValidationResult $result) {
            $result->forceFill(['messages' => new MessageBag(), 'is_passed' => true]);
        });
    }

    public function syncErrors(): MorphMany
    {
        return $this->morphMany(related: PipelinerSyncError::class, name: 'entity');
    }

    public function getIdForHumans(): string
    {
        return $this->project_name;
    }
}
