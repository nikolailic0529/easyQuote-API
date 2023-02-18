<?php

namespace App\Domain\Appointment\Enum;

use App\Domain\Company\Models\Company;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
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
