<?php

namespace App\Domain\QuoteFile\Imports\Concerns;

use App\Domain\QuoteFile\Models\ImportableColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

trait MapsHeaders
{
    /**
     * Header Mapping header → importable_column_id.
     *
     * @var Collection
     */
    protected $headersMapping;

    /**
     * Headers Count.
     *
     * @var int
     */
    protected $headersCount = 0;

    protected function mapHeaders(): void
    {
        $aliasesMapping = $this->getAliasesMapping();

        $this->headersMapping = [];
        $actualMapping = collect();

        $this->headersMapping = $this->getHeader()->mapWithKeys(function ($header, $key) use ($aliasesMapping, $actualMapping) {
            $id = $this->findImportableColumn($header, $aliasesMapping, $actualMapping);

            $header = $this->formatImportableHeader($header, $key);

            if ($id == false) {
                $id = $this->createUnknownImportableColumn($header, $actualMapping);
            }

            $actualMapping->push($id);

            return [$header => $id];
        });

        $this->headersCount = $actualMapping->count();
    }

    private function getAliasesMapping(): Collection
    {
        $systemColumnsAliases = ImportableColumn::query()
            ->orderBy('order')
            ->where('is_system', true)
            ->with('aliases')
            ->get()
            ->pluck('aliases.*.alias', 'id');

        $userColumnsAliases = ImportableColumn::query()
            ->where('is_system', false)
            ->whereHas('aliases', fn ($query) => $query->whereIn('alias', array_filter($this->header)))
            ->with(['aliases' => fn ($query) => $query->groupBy('alias')])
            ->get()
            ->pluck('aliases.*.alias', 'id');

        return $systemColumnsAliases->merge($userColumnsAliases);
    }

    private function getHeader(): Collection
    {
        if (isset($this->header)) {
            return Collection::wrap($this->header);
        }

        return collect();
    }

    private function findImportableColumn(?string $header, Collection $mapping, Collection $actualMapping)
    {
        if (blank($header)) {
            return false;
        }

        return $mapping->search(function ($aliases, $id) use ($header, $actualMapping) {
            if ($actualMapping->contains($id)) {
                return false;
            }

            $quotedHeader = preg_quote($header, '~');

            return count(preg_grep("~^{$quotedHeader}(?![^\h]).*~i", $aliases)) > 0;
        });
    }

    private function createUnknownImportableColumn(string $header, Collection $actualMapping): string
    {
        /** @var \App\Domain\QuoteFile\Models\ImportableColumn $importableColumn */
        $importableColumn = value(function () use ($header, $actualMapping) {
            $alias = $header;
            $name = Str::slug($header, '_');
            $user_id = $this->quoteFile->user_id;

            /** @var \App\Domain\QuoteFile\Models\ImportableColumn $foundColumn */
            $foundColumn = ImportableColumn::query()
                ->where('user_id', $user_id)
                ->where('name', $name)
                ->where('is_temp', true)
                ->whereKeyNot($actualMapping->all())
                ->first();

            if (false === is_null($foundColumn)) {
                return tap($foundColumn, function (ImportableColumn $column) use ($alias) {
                    $column->aliases()->firstOrCreate(['alias' => $alias]);
                });
            }

            return tap(new ImportableColumn(), function (ImportableColumn $column) use ($alias, $user_id, $name, $header) {
                $column->{$column->getKeyName()} = (string) Uuid::generate(4);
                $column->user()->associate($user_id);
                $column->header = $header;
                $column->name = $name;
                $column->is_temp = true;
                $column->is_system = false;

                $column->saveQuietly();

                $column->aliases()->create(['alias' => $alias]);
            });
        });

        return $importableColumn->getKey();
    }

    private function formatImportableHeader(?string $header, int $key): string
    {
        if (blank($header)) {
            return 'Unknown Header '.++$key;
        }

        return $header;
    }
}
