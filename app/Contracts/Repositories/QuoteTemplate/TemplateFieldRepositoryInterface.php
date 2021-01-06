<?php

namespace App\Contracts\Repositories\QuoteTemplate;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\QuoteTemplate\{
    StoreTemplateFieldRequest,
    UpdateTemplateFieldRequest
};
use App\Models\Template\TemplateField;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

interface TemplateFieldRepositoryInterface
{
    /**
     * Data for creating a new Template Field.
     *
     * @return Collection
     */
    public function data(): Collection;

    /**
     * Get all Template Fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Get all system defined template fields.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allSystem(): EloquentCollection;

    /**
     * Search over Template Fields.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Template Field by id.
     *
     * @param string $id
     * @return TemplateField
     */
    public function find(string $id): TemplateField;

    /**
     * Create Template Field.
     *
     * @param StoreTemplateFieldRequest $request
     * @return TemplateField
     */
    public function create(StoreTemplateFieldRequest $request): TemplateField;

    /**
     * Update specified Template Field.
     *
     * @param UpdateTemplateFieldRequest $request
     * @param string $id
     * @return TemplateField
     */
    public function update(UpdateTemplateFieldRequest $request, string $id): TemplateField;

    /**
     * Delete specified Template Field.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Activate specified Template Field.
     *
     * @param string $id
     * @return bool
     */
    public function activate(string $id): bool;

    /**
     * Deactivate specified Template Field.
     *
     * @param string $id
     * @return bool
     */
    public function deactivate(string $id): bool;
}
