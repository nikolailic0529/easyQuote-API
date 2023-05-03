<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class DistributionMapping extends DataTransferObject
{
    /**
     * @Constraints\Uuid()
     */
    public string $worldwide_distribution_id;

    /**
     * @Constraints\Uuid()
     */
    public string $template_field_id;

    /**
     * @Constraints\Uuid()
     */
    public ?string $importable_column_id;

    public bool $is_default_enabled;

    public bool $is_preview_visible;

    public bool $is_editable;
}
