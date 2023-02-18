<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Note\Models\Note;
use App\Domain\Task\Models\Task;
use App\Domain\Worldwide\Models\Opportunity;
use App\Foundation\Log\Contracts\LoggerAware;
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
            $unlinked = (new $class())->newQuery()->whereNotNull('pl_reference')->update(['pl_reference' => null]);

            $this->logger->info(sprintf('Unlinked %s: %d.', Str::plural(class_basename($class)), $unlinked));
        }
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn () => $this->logger = $logger);
    }
}
