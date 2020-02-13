<?php

namespace App\Imports\Concerns;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use Illuminate\Support\Collection;
use Str;

trait MapsHeaders
{
    /**
     * Header Mapping header → importable_column_id
     *
     * @var Collection
     */
    protected $headersMapping;

    /**
     * Headers Count.
     *
     * @var integer
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

    protected function importRepository(): ImportableColumnRepositoryInterface
    {
        return app(ImportableColumnRepositoryInterface::class);
    }

    private function getAliasesMapping(): Collection
    {
        $systemColumnsAliases = $this->importRepository()->allSystem()->pluck('aliases.*.alias', 'id');
        $userColumnsAliases = $this->importRepository()->userColumns(array_filter($this->header))->pluck('aliases.*.alias', 'id');

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
        $alias = $header;
        $name = Str::slug($header, '_');
        $user_id = $this->quoteFile->user_id;

        $importableColumn = $this->importRepository()->firstOrCreate(
            compact('name'),
            compact('header', 'name', 'user_id'),
            function ($query) use ($actualMapping) {
                $query->whereNotIn('id', $actualMapping->toArray());
            }
        );

        $importableColumn->aliases()->firstOrCreate(compact('alias'));

        return $importableColumn->id;
    }

    private function formatImportableHeader(?string $header, int $key): string
    {
        if (blank($header)) {
            return 'Unknown Header ' . ++$key;
        }

        return $header;
    }
}
