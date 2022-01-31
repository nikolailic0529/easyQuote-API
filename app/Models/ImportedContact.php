<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed|string $contact_type
 * @property mixed|null $first_name
 * @property mixed|null $last_name
 * @property mixed|null $email
 * @property mixed|null $phone
 * @property mixed|null $job_title
 * @property false|mixed $is_verified
 * @property mixed|string $contact_name
 */
class ImportedContact extends Model
{
    use Uuid;

    public bool $is_primary = false;

    protected $guarded = [];
}
