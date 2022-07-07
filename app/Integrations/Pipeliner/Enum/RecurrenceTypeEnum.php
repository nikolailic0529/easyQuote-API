<?php

namespace App\Integrations\Pipeliner\Enum;

enum RecurrenceTypeEnum: string
{
    case NotSet = 'NotSet';
    case Daily = 'Daily';
    case Weekly = 'Weekly';
    case MonthlyRelative = 'MonthlyRelative';
    case MonthlyAbsolute = 'MonthlyAbsolute';
    case YearlyRelative = 'YearlyRelative';
    case YearlyAbsolute = 'YearlyAbsolute';
    case AfterNDays = 'AfterNDays';
    case AfterNWeeks = 'AfterNWeeks';
    case AfterNMonths = 'AfterNMonths';
    case AfterNYears = 'AfterNYears';
}