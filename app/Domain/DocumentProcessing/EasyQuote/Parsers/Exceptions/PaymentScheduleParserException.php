<?php

namespace App\Domain\DocumentProcessing\EasyQuote\Parsers\Exceptions;

class PaymentScheduleParserException extends \Exception
{
    public static function couldNotMatchPaymentDates(): static
    {
        return new static('Could not match the payment dates');
    }

    public static function couldNotMatchPaymentValues(): static
    {
        return new static('Could not match the payment values');
    }
}
