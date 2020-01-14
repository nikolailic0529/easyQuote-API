<?php

namespace App\Repositories;

use App\Contracts\Repositories\AccessAttemptRepositoryInterface;
use App\Models\AccessAttempt;
use Arr;

class AccessAttemptRepository implements AccessAttemptRepositoryInterface
{
    /** @var \App\Models\AccessAttempt */
    protected $attempt;

    /**
     * Time in seconds to determine if attempt was recently created with the same attributes.
     *
     * @var integer
     */
    protected static $throttleTime = AT_THROTTLE_TIME;

    public function __construct(AccessAttempt $attempt)
    {
        $this->attempt = $attempt;
    }

    public function all()
    {
        return $this->attempt->all();
    }

    public function create(array $attributes): AccessAttempt
    {
        return $this->attempt->create($attributes);
    }

    public function retrieveOrCreate(array $attributes): AccessAttempt
    {
        $query = $this->attempt->query()
            ->where([
                'email' => data_get($attributes, 'email'),
                'ip_address' => data_get($attributes, 'local_ip'),
            ])
            ->latest()
            ->where('created_at', '>', now()->subSeconds(static::$throttleTime));

        if (!is_null($attempt = $query->first())) {
            return tap($attempt)->markAsPreviouslyKnown();
        }

        return $this->attempt->create($attributes);
    }
}
