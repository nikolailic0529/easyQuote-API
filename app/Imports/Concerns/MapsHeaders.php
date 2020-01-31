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
        throw_unless(property_exists($this, 'header'), new \InvalidArgumentException('The header property must be defined.'));

        $aliasesMapping = $this->importRepository()->allSystem()->pluck('aliases.*.alias', 'id');
        $userAliasesMapping = $this->importRepository()->userColumns(array_filter($this->header))->pluck('aliases.*.alias', 'id');
        $aliasesMapping = $aliasesMapping->merge($userAliasesMapping);

        $this->headersMapping = [];
        $mapping = collect([]);

        $this->headersMapping = collect($this->header)->mapWithKeys(function ($header, $key) use ($aliasesMapping, $mapping) {
            $column = false;
            $column_num = $key + 1;

            if (filled($header)) {
                $column = $aliasesMapping->search(function ($aliases, $importable_column_id) use ($header, $mapping) {
                    if ($mapping->contains($importable_column_id)) {
                        return false;
                    }

                    $matchingHeader = preg_quote($header, '~');
                    $match = preg_grep("~^{$matchingHeader}.*?~i", $aliases);

                    return filled($match);
                });
            }

            blank($header) && $header = "Unknown Header {$column_num}";

            if (!$column) {
                $alias = $header;
                $name = Str::columnName($header);
                $user_id = $this->quoteFile->user_id;

                $importableColumn = $this->importRepository()->firstOrCreate(
                    compact('name'),
                    compact('header', 'name', 'user_id'),
                    function ($query) use ($mapping) {
                        $query->whereNotIn('id', $mapping->toArray());
                    }
                );

                $importableColumn->aliases()->firstOrCreate(compact('alias'));
                $column = $importableColumn->id;
            }

            $mapping->push($column);

            return [$header => $column];
        });

        $this->headersCount = $mapping->count();
    }

    protected function importRepository(): ImportableColumnRepositoryInterface
    {
        return app(ImportableColumnRepositoryInterface::class);
    }
}
