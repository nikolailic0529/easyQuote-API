<?php

namespace App\Domain\Worldwide\Enum;

final class PackQuoteStage extends \App\Foundation\Support\Enum\Enum
{
    const INITIATED = 1;
    const OPPORTUNITY = 10;
    const CONTACTS = 20;
    const ASSETS_CREATE = 40;
    const ASSETS_REVIEW = 50;
    const MARGIN = 70;
    const DISCOUNT = 90;
    const DETAIL = 99;
    const COMPLETED = 100
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
