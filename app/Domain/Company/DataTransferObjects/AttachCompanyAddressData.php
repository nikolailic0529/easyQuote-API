<?php

namespace App\Domain\Company\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class AttachCompanyAddressData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $id;

    public bool $is_default;
}
