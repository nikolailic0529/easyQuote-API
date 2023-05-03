<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Models;

final class ReplicatedContactsData
{
    /**
     * ReplicatedContactsData constructor.
     */
    public function __construct(protected array $contactModels, protected array $contactPivots)
    {
    }

    /**
     * @return \App\Domain\Contact\Models\Contact[]
     */
    public function getContactModels(): array
    {
        return $this->contactModels;
    }

    /**
     * @return array[]
     */
    public function getContactPivots(): array
    {
        return $this->contactPivots;
    }
}
