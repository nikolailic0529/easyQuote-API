<?php

namespace App\Services\Company\DataEnrichment\Sources;

use App\Services\Company\DataEnrichment\Models\CompanyProfile;

interface Source
{
    public function find(string $name): ?CompanyProfile;

    public function get(string $number): ?CompanyProfile;

    /**
     * @return list<string>
     */
    public function getSupportedCountries(): array;
}