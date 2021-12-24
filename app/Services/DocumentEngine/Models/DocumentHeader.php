<?php

namespace App\Services\DocumentEngine\Models;

use DateTimeInterface;

final class DocumentHeader extends DocumentEngineEntity
{
    public function __construct(protected string $headerReference,
                                protected string $headerName,
                                protected bool $isSystem,
                                protected ?DateTimeInterface $createdAt,
                                protected ?DateTimeInterface $updatedAt,
                                protected array $headerAliases = [])
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
    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->isSystem;
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

    /**
     * @return DocumentHeaderAlias[]
     */
    public function getHeaderAliases(): array
    {
        return $this->headerAliases;
    }
}