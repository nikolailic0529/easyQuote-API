<?php

namespace App\Domain\DocumentEngine\Models;

final class DocumentHeaderAlias extends DocumentEngineEntity
{
    public function __construct(
        protected string $headerReference,
        protected string $aliasReference,
        protected string $aliasName,
        protected ?\DateTimeInterface $createdAt,
        protected ?\DateTimeInterface $updatedAt,
    ) {
    }

    public function getHeaderReference(): string
    {
        return $this->headerReference;
    }

    public function getAliasReference(): string
    {
        return $this->aliasReference;
    }

    public function getAliasName(): string
    {
        return $this->aliasName;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
