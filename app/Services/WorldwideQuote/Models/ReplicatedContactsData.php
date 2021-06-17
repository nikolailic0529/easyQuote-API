<?php

namespace App\Services\WorldwideQuote\Models;

final class ReplicatedContactsData
{
    /**
     * ReplicatedContactsData constructor.
     * @param array $contactModels
     * @param array $contactPivots
     */
    public function __construct(protected array $contactModels, protected array $contactPivots)
    {
    }

    /**
     * @return \App\Models\Contact[]
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
