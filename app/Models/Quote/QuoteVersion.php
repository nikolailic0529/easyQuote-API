<?php

namespace App\Models\Quote;

use App\Scopes\VersionScope;

class QuoteVersion extends BaseQuote
{
    protected $is_using_original = false;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new VersionScope);
    }

    public function getVersionNumberAttribute($value): int
    {
        return $value ?? 1;
    }

    public function getVersionNameAttribute(): string
    {
        return "{$this->user->first_name} {$this->user->last_name} {$this->version_number}";
    }

    public function getParentIdAttribute(): ?string
    {
        return $this->laravel_through_key ?? $this->pivot->quote_id ?? $this->id ?? null;
    }

    public function getIsUsingAttribute(): bool
    {
        return $this->is_using_original || (isset($this->pivot) && $this->pivot->is_using);
    }

    public function getIsOriginalAttribute(): bool
    {
        return $this->parent_id === $this->id;
    }

    public function toSelectionArray(): array
    {
        $this->setRelation('user', $this->cached_relations->user ?? $this->user);

        return [
            'id' => $this->id,
            'name'  => $this->versionName,
            'is_using' => $this->isUsing,
            'is_original' => $this->isOriginal,
            'drafted_at' => $this->drafted_at
        ];
    }

    public function setIsUsingOriginalAttribute(bool $value): void
    {
        $this->is_using_original = $value;
    }
}
