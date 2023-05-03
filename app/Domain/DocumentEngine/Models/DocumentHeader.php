<?php

namespace App\Domain\DocumentEngine\Models;

final class DocumentHeader extends DocumentEngineEntity
{
    public function __construct(protected string $headerReference,
                                protected string $headerName,
                                protected bool $isSystem,
                                protected ?\DateTimeInterface $createdAt,
                                protected ?\DateTimeInterface $updatedAt,
                                protected array $headerAliases = [])
    {
    }

    public function getHeaderReference(): string
    {
        return $this->headerReference;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
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
