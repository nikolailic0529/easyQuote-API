<?php

namespace App\Contracts\Repositories\System;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ActivityRepositoryInterface
{
    /**
     * Retrieve all activities.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over all activities.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Query Builder instance for activities.
     *
     * @return Builder
     */
    public function query(): Builder;

    /**
     * Query Builder instance for activities by passed subject.
     *
     * @param string $subject_id
     * @return Builder
     */
    public function subjectQuery(string $subject_id): Builder;

    /**
     * Retrieve a summary by the activities.
     *
     * @return Collection
     */
    public function summary(): Collection;
}
