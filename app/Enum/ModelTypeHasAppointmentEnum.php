<?php

namespace App\Enum;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Database\Eloquent\Model;

enum ModelTypeHasAppointmentEnum: string
{
    case Company = 'Company';
    case Quote = 'Quote';
    case WorldwideQuote = 'WorldwideQuote';
    case Opportunity = 'Opportunity';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Company => Company::class,
            self::Quote => Quote::class,
            self::WorldwideQuote => WorldwideQuote::class,
            self::Opportunity => Opportunity::class,
        };
    }
}