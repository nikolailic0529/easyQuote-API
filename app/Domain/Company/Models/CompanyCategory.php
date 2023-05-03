<?php

namespace App\Domain\Company\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCategory extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [];
}
