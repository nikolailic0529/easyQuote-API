<?php

namespace App\Services\DocumentEngine\Models;

use DateTimeInterface;

final class DocumentHeaderAlias extends DocumentEngineEntity
{
    public function __construct(
        protected string $headerReference,
        protected string $aliasReference,
        protected string $aliasName,
        protected ?DateTimeInterface $createdAt,
        protected ?DateTimeInterface $updatedAt,
    )
    {
    }

    /**
     * @return string
     */
    public function getHeaderReference(): string
    {
        return $this->headerReference;
    }

    /**
     * @return string
     */
    public function getAliasReference(): string
    {
        return $this->aliasReference;
    }

    /**
     * @return string
     */
    public function getAliasName(): string
    {
        return $this->aliasName;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }
}