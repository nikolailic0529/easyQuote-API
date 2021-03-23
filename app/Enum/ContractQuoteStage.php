<?php

namespace App\Enum;

final class ContractQuoteStage extends Enum
{
    const
        INITIATED = 1,
        OPPORTUNITY = 10,
        IMPORT = 20,
        MAPPING = 40,
        REVIEW = 50,
        MARGIN = 70,
        DISCOUNT = 90,
        DETAIL = 99,
        COMPLETED = 100
    ;

    protected static array $labels = [
        self::INITIATED => 'Initiated',
        self::OPPORTUNITY => 'Opportunity',
        self::IMPORT => 'Import',
        self::MAPPING => 'Mapping',
        self::REVIEW => 'Review',
        self::MARGIN => 'Margin',
        self::DISCOUNT => 'Discount',
        self::DETAIL => 'Additional Detail',
        self::COMPLETED => 'Complete',
    ];

    public static function getLabels(): array
    {
        return static::$labels;
    }

    public static function getValueOfLabel(string $label)
    {
        return array_search($label, static::$labels, true);
    }

    public static function getLabelOfValue(int $value)
    {
        return static::$labels[$value];
    }
}
