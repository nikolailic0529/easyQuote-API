<?php

namespace App\DTO\UnifiedQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UnifiedQuotesRequestData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $user_id = null;

    public bool $show_rescue_entities = true;

    public bool $show_worldwide_entities = true;

    public bool $show_own_entities_only = false;

    /**
     * @Constraints\IsTrue(message="At least one entity type must be defined.")
     *
     * @return bool
     */
    public function isAnyEntityTypeDefined(): bool
    {
        return $this->show_worldwide_entities || $this->show_rescue_entities;
    }
}
