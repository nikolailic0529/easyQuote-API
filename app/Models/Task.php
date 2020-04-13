<?php

namespace App\Models;

use App\Traits\{
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToUser,
    BelongsToUsers,
    HasAttachments,
    NotifiableModel,
    Uuid,
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Quote\Quote;

class Task extends Model
{
    public const TASKABLES = [Quote::class];
    
    use Uuid,
        Multitenantable,
        SoftDeletes,
        LogsActivity,
        BelongsToUsers,
        BelongsToUser,
        HasAttachments,
        NotifiableModel;

    protected $fillable = [
        'name', 'content', 'priority', 'expiry_date', 'task_template_id', 'taskable_id', 'taskable_type'
    ];

    protected $casts = [
        'content' => 'array'
    ];

    protected $dates = [
        'expiry_date'
    ];

    protected static $logAttributes = [
        'name', 'priority', 'expiry_date'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function taskable(): MorphTo
    {
        return $this->morphTo('taskable');
    }
}
