<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\QuoteNote;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface QuoteNoteRepositoryInterface
{
    /**
     * Paginate existing quote notes with specific clause.
     *
     * @param array $clause
     * @param string|null $search
     * @return mixed
     */
    public function paginate(array $clause = [], ?string $search = null);

    /**
     * Find the specified quote note by key.
     *
     * @param string $id
     * @return QuoteNote
     * @throws ModelNotFoundException
     */
    public function find(string $id): QuoteNote;

    /**
     * Create a new quote note with specified attributes.
     *
     * @param array $attributes
     * @return QuoteNote
     */
    public function create(array $attributes): QuoteNote;

    /**
     * Update specified quote note with given attributes.
     *
     * @param string $id
     * @param array $attributes
     * @return QuoteNote
     */
    public function update(string $id, array $attributes): QuoteNote;

    /**
     * Delete soecified quote note.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;
}
