<?php

namespace App\Domain\Company\Services\DataEnrichment\Sources;

use App\Domain\Company\Services\DataEnrichment\Models\CompanyProfile;

interface Source
{
    public function find(string $name): ?CompanyProfile;

    public function get(string $number): ?CompanyProfile;

    /**
     * @return list<string>
     */
    public function getSupportedCountries(): array;
}
