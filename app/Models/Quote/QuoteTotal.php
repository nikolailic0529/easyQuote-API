<?php

namespace App\Models\Quote;

use App\Traits\BelongsToQuote;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class QuoteTotal extends Model
{
    use Uuid, BelongsToQuote;

    protected $fillable = ['quote_id', 'total_price', 'rfq_number', 'valid_until_date', 'quote_created_at', 'quote_submitted_at'];

    protected $dates = [
        'quote_created_at', 'quote_submitted_at', 'valid_until_date'
    ];
}
