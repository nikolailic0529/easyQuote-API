<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteNoteRepositoryInterface;
use App\Repositories\Concerns\FiltersQuery;
use App\Models\Quote\QuoteNote;

class QuoteNoteRepository implements QuoteNoteRepositoryInterface
{
    use FiltersQuery;

    protected QuoteNote $quoteNote;

    public function __construct(QuoteNote $quoteNote)
    {
        $this->quoteNote = $quoteNote;
    }

    public function paginate(array $clause = [], ?string $search = null)
    {
        $query = $this->quoteNote->query()->with('user')->where($clause);

        if ($search) {
            $query->where('text', 'like', '%'.$search.'%');
        }

        $this->filterQuery($query);

        return $query->apiPaginate();
    }

    public function find(string $id): QuoteNote
    {
        return $this->quoteNote->whereKey($id)->firstOrFail();
    }

    public function create(array $attributes): QuoteNote
    {
        return $this->quoteNote->create($attributes);
    }

    public function update(string $id, array $attributes): QuoteNote
    {
        return tap($this->find($id))->update($attributes);
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
        ];
    }
}
