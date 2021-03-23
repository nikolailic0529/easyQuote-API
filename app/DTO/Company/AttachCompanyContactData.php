<?php

namespace App\DTO\Company;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class AttachCompanyContactData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $id;

    /**
     * @var bool
     */
    public bool $is_default;
}
