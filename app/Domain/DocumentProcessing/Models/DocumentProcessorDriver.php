<?php

namespace App\Domain\DocumentProcessing\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

class DocumentProcessorDriver extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
