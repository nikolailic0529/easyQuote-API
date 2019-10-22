<?php namespace App\Contracts\Repositories\QuoteTemplate;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\QuoteTemplate \ {
    StoreTemplateFieldRequest,
    UpdateTemplateFieldRequest
};
use App\Models\QuoteTemplate\TemplateField;
use Illuminate\Support\Collection;

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
     * Search over User's Template Fields.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

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
