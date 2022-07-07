<?php

namespace App\Enum;

enum DateFormatEnum: string
{
    case Auto = 'Auto';
    case DayDashMonthDashFullYear = 'DD-MM-YYYY';
    case MonthDashDayDashFullYear = 'MM-DD-YYYY';
    case FullYearDashDayDashMonth = 'YYYY-DD-MM';
    case FullYearDashMonthDashDay = 'YYYY-MM-DD';
    case DaySlashMonthSlashFullYear = 'DD/MM/YYYY';
    case MonthSlashDaySlashFullYear = 'MM/DD/YYYY';
}