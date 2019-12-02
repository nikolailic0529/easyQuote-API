<?php

namespace App\Contracts\Repositories\System;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
     * Meta data for activities.
     *
     * @return array
     */
    public function meta(): array;

    /**
     * Retrieve the activites by specified subject.
     *
     * @param string $subject
     * @return mixed
     */
    public function subjectActivities(string $subject);

    /**
     * Search over the activities by specified subject.
     *
     * @param string $subject
     * @param string $query
     * @return mixed
     */
    public function searchSubjectActivities(string $subject, string $query = '');

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
     * Find the specified subject.
     *
     * @param string $subject_id
     * @return Model
     */
    public function findSubject(string $subject_id): Model;

    /**
     * Retrieve a summary by the activities.
     *
     * @return Collection
     */
    public function summary(): Collection;

    /**
     * Export the subject activities in PDF/CSV.
     *
     * @return mixed
     */
    public function export(string $type);

    /**
     * Export the subject activities in PDF/CSV.
     *
     * @param string $subject_id
     * @return void
     */
    public function exportSubject(string $subject_id, string $type);
}
