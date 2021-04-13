<?php

namespace App\Services\WorldwideQuote\Models;

final class ReplicatedContactsData
{
    /** @var \App\Models\Contact[] */
    protected array $contactModels;

    /** @var array[] */
    protected array $contactPivots;

    /**
     * ReplicatedContactsData constructor.
     * @param array $contactModels
     * @param array $contactPivots
     */
    public function __construct(array $contactModels, array $contactPivots)
    {
        $this->contactModels = $contactModels;
        $this->contactPivots = $contactPivots;
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
