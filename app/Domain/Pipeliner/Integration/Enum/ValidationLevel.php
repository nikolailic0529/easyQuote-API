<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum ValidationLevel
{
    case FULL;
    case SKIP_UNCHANGED_FIELDS;
    case SKIP_USER_DEFINED_VALIDATIONS;
    case SKIP_READONLY_VALIDATION;
    case SKIP_RELATIONSHIP_VALIDATION;
    case SKIP_FIELD_VALUE_VALIDATION;
    case SKIP_INVALID_RECALCULATIONS;
    case SKIP_STEP_CHECKLIST_VALIDATION;
    case SKIP_RECALCULATION;
    case SKIP_ALL;
}
