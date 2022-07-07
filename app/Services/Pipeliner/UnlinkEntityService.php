<?php

namespace App\Services\Pipeliner;

use App\Contracts\LoggerAware;
use App\Models\Address;
use App\Models\Appointment\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Task\Task;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class UnlinkEntityService implements LoggerAware
{
    public function __construct(protected LoggerInterface $logger = new NullLogger())
    {
    }

    public function unlink(): void
    {
        $models = [
            Company::class,
            Opportunity::class,
            Address::class,
            Contact::class,
            Appointment::class,
            Task::class,
            Note::class,
        ];

        foreach ($models as $class) {
            $unlinked = (new $class)->newQuery()->whereNotNull('pl_reference')->update(['pl_reference' => null]);

            $this->logger->info(sprintf("Unlinked %s: %d.", Str::plural(class_basename($class)), $unlinked));
        }
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}