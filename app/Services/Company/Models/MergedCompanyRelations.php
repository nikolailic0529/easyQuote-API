<?php

namespace App\Services\Company\Models;

use Illuminate\Database\Eloquent\Collection;

final class MergedCompanyRelations implements \JsonSerializable
{
    public function __construct(
        public readonly Collection $assets = new Collection(),
        public readonly Collection $attachments = new Collection(),
        public readonly Collection $opportunitiesWherePrimaryAccount = new Collection(),
        public readonly Collection $opportunitiesWhereEndUser = new Collection(),
        public readonly Collection $notes = new Collection(),
        public readonly Collection $addresses = new Collection(),
        public readonly Collection $contacts = new Collection(),
        public readonly Collection $vendors = new Collection(),
        public readonly Collection $requestsForQuote = new Collection(),
        public readonly Collection $images = new Collection(),
    )
    {
    }

    public static function new(): MergedCompanyRelations
    {
        return new MergedCompanyRelations();
    }

    public function jsonSerialize(): array
    {
        $reflection = new \ReflectionClass($this);

        $array = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $array[$property->getName()] = $property->getValue($this)->modelKeys();
        }

        return $array;
    }
}