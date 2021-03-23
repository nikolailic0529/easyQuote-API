<?php

namespace App\Models;

use App\Contracts\SearchableEntity;
use App\Models\Quote\WorldwideQuote;
use App\Models\Template\ContractTemplate;
use App\Services\SalesOrder\SalesOrderNumberHelper;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class SalesOrder
 *
 * @property string|null $user_id
 * @property string|null $worldwide_quote_id
 * @property string|null $contract_template_id
 *
 * @property string|null $order_number
 * @property string|null $vat_number
 * @property string|null $vat_type
 * @property string|null $customer_po
 * @property float|null $exchange_rate
 *
 * @property string|null $order_date
 *
 * @property string|null $submitted_at
 * @property string|null $activated_at
 *
 * @property WorldwideQuote|null $worldwideQuote
 * @property ContractTemplate|null $contractTemplate
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $status
 * @property string|null $status_reason
 * @property string|null $failure_reason
 */
class SalesOrder extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function worldwideQuote(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuote::class);
    }

    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'customer_name' => $this->worldwideQuote->company->name,
            'order_number' => SalesOrderNumberHelper::makeSalesOrderNumber(
                $this->worldwideQuote->contractType->type_short_name,
                $this->worldwideQuote->sequence_number
            ),
            'order_type' => $this->worldwideQuote->contractType->type_short_name,
            'rfq_number' => $this->worldwideQuote->quote_number,
            'created_at' => transform($this->created_at, fn(Carbon $carbon) => $carbon->toDateString())
        ];
    }
}
