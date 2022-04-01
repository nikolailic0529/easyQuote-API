<?php

namespace App\Services\Opportunity;

class ContractTypeResolver
{
    const DEFAULT_OPP_TYPE = CT_PACK;

    public function __invoke(?string $value): string
    {
        if (is_null($value)) {
            return self::DEFAULT_OPP_TYPE;
        }

        return match (trim(strtolower($value))) {
            'pack' => CT_PACK,
            'contract' => CT_CONTRACT,
            default => self::DEFAULT_OPP_TYPE,
        };
    }
}