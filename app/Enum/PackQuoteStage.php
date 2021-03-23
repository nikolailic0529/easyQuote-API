<?php

namespace App\Enum;

final class PackQuoteStage extends Enum
{
    const
        INITIATED = 1,
        OPPORTUNITY = 10,
        CONTACTS = 20,
        ASSETS_CREATE = 40,
        ASSETS_REVIEW = 50,
        MARGIN = 70,
        DISCOUNT = 90,
        DETAIL = 99,
        COMPLETED = 100
    ;

    protected static array $labels = [
        self::INITIATED => 'Initiated',
        self::OPPORTUNITY => 'Opportunity',
        self::CONTACTS => 'Contacts',
        self::ASSETS_CREATE => 'Assets Creation',
        self::ASSETS_REVIEW => 'Assets Review',
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
