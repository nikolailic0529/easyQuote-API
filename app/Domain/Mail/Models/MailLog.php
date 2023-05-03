<?php

namespace App\Domain\Mail\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\MailLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string      $message_id Message Id
 * @property array|null  $cc         CC Header
 * @property array|null  $bcc        BCC Header
 * @property array|null  $reply_to   ReplyTo Header
 * @property array       $from       From Header
 * @property array       $to         To Header
 * @property string|null $subject    Message Subject
 * @property string      $body       Message Body
 * @property Carbon      $sent_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|MailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailLog query()
 */
class MailLog extends Model implements SearchableEntity
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $table = 'mail_log';

    protected $casts = [
        'cc' => 'array',
        'bcc' => 'array',
        'reply_to' => 'array',
        'from' => 'array',
        'to' => 'array',
        'sent_at' => 'datetime',
    ];

    protected static function newFactory(): MailLogFactory
    {
        return MailLogFactory::new();
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'from' => array_keys($this->from ?? []),
            'to' => array_keys($this->to ?? []),
            'subject' => $this->subject,
        ];
    }
}
