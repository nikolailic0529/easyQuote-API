<?php

namespace App\Models;

use App\Contracts\HasOwnAppointments;
use App\Contracts\HasOwner;
use App\Contracts\HasOwnNotes;
use App\Contracts\LinkedToAppointments;
use App\Contracts\LinkedToTasks;
use App\Contracts\SearchableEntity;
use App\Enum\OpportunityStatus;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\ModelHasAppointments;
use App\Models\Note\ModelHasNotes;
use App\Models\Note\Note;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeline\PipelineStage;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\Quote\WorldwideQuote;
use App\Models\Task\Task;
use App\Traits\HasTimestamps;
use App\Traits\Uuid;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class Opportunity
 *
 * @property string|null $pl_reference
 * @property string|null $user_id
 * @property string|null $pipeline_id
 * @property string|null $pipeline_stage_id
 * @property string|null $contract_type_id
 * @property string|null $primary_account_id
 * @property string|null $end_user_id
 * @property bool|null $are_end_user_addresses_available
 * @property bool|null $are_end_user_contacts_available
 * @property string|null $primary_account_contact_id
 * @property string|null $imported_primary_account_id
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
 *
 * @property string|null $opportunity_start_date
 * @property bool|null $is_opportunity_start_date_assumed
 * @property string|null $opportunity_end_date
 * @property bool|null $is_opportunity_end_date_assumed
 * @property string|null $opportunity_closing_date
 * @property int|null $contract_duration_months
 * @property bool|null $is_contract_duration_checked
 * @property string|null $customer_order_date
 * @property string|null $purchase_order_date
 * @property string|null $supplier_order_date
 * @property string|null $supplier_order_transaction_date
 * @property string|null $supplier_order_confirmation_date
 * @property string|null $expected_order_date
 *
 * @property float|null $opportunity_amount
 * @property string|null $opportunity_amount_currency_code
 * @property float|null $purchase_price
 * @property string|null $purchase_price_currency_code
 * @property float|null $list_price
 * @property string|null $list_price_currency_code
 * @property float|null $estimated_upsell_amount
 * @property string|null $estimated_upsell_amount_currency_code
 * @property float|null $margin_value
 *
 * @property float|null $base_opportunity_amount
 * @property float|null $base_list_price
 * @property float|null $base_purchase_price
 *
 * @property string|null $account_manager_name
 * @property string|null $service_level_agreement_id
 * @property string|null $sale_unit_name
 * @property string|null $competition_name
 * @property string|null $drop_in // ?
 * @property string|null $lead_source_name
 *
 * @property bool|null $has_higher_sla
 * @property bool|null $is_multi_year
 * @property bool|null $has_additional_hardware
 * @property bool|null $has_service_credits
 *
 * @property string|null $remarks
 * @property string|null $notes // Description
 * @property string|null $personal_rating
 * @property int|null $ranking
 * @property string|null $campaign_name
 *
 * @property int|null $order_in_pipeline_stage
 * @property string|null $sale_action_name // {"Preparation", "Special Bid Required", "Quote Ready", "Customer Contact", "Customer Order OK", "PO Placed", "Processed on BC", "Closed"}
 *
 * @property OpportunityStatus|null $status
 * @property string|null $status_reason
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read \App\Models\Pipeline\Pipeline|null $pipeline
 * @property-read PipelineStage|null $pipelineStage
 * @property-read ContractType|null $contractType
 * @property-read Company|null $primaryAccount
 * @property-read Company|null $endUser
 * @property-read ImportedCompany|null $importedPrimaryAccount
 * @property-read Contact|null $primaryAccountContact
 * @property-read ImportedContact|null $importedPrimaryAccountContact
 * @property-read User|null $accountManager
 * @property-read Collection<OpportunitySupplier>|OpportunitySupplier[] $opportunitySuppliers
 * @property-read Collection<WorldwideQuote>|WorldwideQuote[] $worldwideQuotes
 * @property-read Collection<int, Task>|Task[] $tasks
 * @property-read bool|null $quotes_exist
 */
class Opportunity extends Model implements SearchableEntity, HasOwner, LinkedToAppointments, HasOwnAppointments, LinkedToTasks, HasOwnNotes
{
    use Uuid, SoftDeletes, HasRelationships, HasFactory, HasTimestamps;

    protected $guarded = [];

    protected $casts = [
        'status' => OpportunityStatus::class,
    ];

    protected static function newFactory(): OpportunityFactory
    {
        return OpportunityFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
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
        return $this->hasMany(OpportunitySupplier::class)->oldest();
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
            'opportunity_amount' => (string)$this->opportunity_amount,
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

    public function pipelinerSyncLogs(): MorphMany
    {
        return $this->morphMany(related: PipelinerSyncStrategyLog::class, name: 'model');
    }

    public function latestPipelinerSyncLog(): MorphOne
    {
        return $this->morphOne(related: PipelinerSyncStrategyLog::class, name: 'model')->latestOfMany('updated_at');
    }
}

