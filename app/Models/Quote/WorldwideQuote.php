<?php

namespace App\Models\Quote;

use App\Contracts\SearchableEntity;
use App\Models\Addressable;
use App\Models\Company;
use App\Models\Contactable;
use App\Models\ContractType;
use App\Models\Opportunity;
use App\Models\SalesOrder;
use App\Models\Task;
use App\Models\User;
use App\Models\WorldwideQuoteAsset;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
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
 * @property-read bool|null $sales_order_exists
 *
 * @property User $user
 * @property Collection<WorldwideQuoteVersion>|\App\Models\Quote\WorldwideQuoteVersion[] $versions
 * @property WorldwideQuoteVersion|null $activeVersion
 * @property SalesOrder|null $salesOrder
 * @property ContractType|null $contractType
 * @property Opportunity|null $opportunity
 * @property Collection<Addressable> $referencedAddressPivotsOfPrimaryAccount
 * @property Collection<Contactable> $referencedContactPivotsOfPrimaryAccount
 */
class WorldwideQuote extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes, HasRelationships;

    protected $guarded = [];

    public function assets(): HasManyThrough
    {
        return $this->hasManyThrough(WorldwideQuoteAsset::class, WorldwideQuoteVersion::class, 'id', 'worldwide_quote_id', 'active_version_id');
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
                Opportunity::class, Company::class
            ],
            [
                'id',
                'id',
                'addressable_id'
            ],
            [
                'opportunity_id',
                'primary_account_id',
                null
            ]
        );
    }


    public function referencedContactPivotsOfPrimaryAccount(): HasManyThrough
    {
        return $this->hasManyDeep(
            Contactable::class,
            [
                Opportunity::class, Company::class
            ],
            [
                'id',
                'id',
                'contactable_id'
            ],
            [
                'opportunity_id',
                'primary_account_id',
                null
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

    public function worldwideQuoteNotes(): HasMany
    {
        return $this->hasMany(WorldwideQuoteNote::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'rfq_number' => $this->quote_number,
            'customer_name' => optional($this->opportunity->primaryAccount)->name,
            'company_name' => optional($this->activeVersion->company)->name,
            'contract_type_name' => $this->contractType->type_short_name,
            'valid_until_date' => $this->opportunity->opportunity_closing_date,
            'support_start_date' => $this->opportunity->opportunity_start_date,
            'support_end_date' => $this->opportunity->opportunity_end_date,
            'created_at' => optional($this->created_at)->toDateString(),
        ];
    }
}
