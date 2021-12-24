<?php

namespace App\Services\Company;

use App\Enum\Lock;
use App\Events\CompanyNote\CompanyNoteCreated;
use App\Events\CompanyNote\CompanyNoteDeleted;
use App\Events\CompanyNote\CompanyNoteUpdated;
use App\Models\Company;
use App\Models\CompanyNote;
use App\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyNoteEntityService
{
    public function __construct(protected ConnectionInterface $connection,
                                protected LockProvider $lockProvider,
                                protected ValidatorInterface $validator,
                                protected EventDispatcher $eventDispatcher)
    {
    }

    public function createCompanyNote(string $noteText, Company $company, ?User $user = null)
    {
        $violations = $this->validator->validate($noteText, [new NotBlank]);

        count($violations) && throw new ValidationFailedException($noteText, $violations);

        return tap(new CompanyNote(), function (CompanyNote $companyNote) use ($noteText, $company, $user) {

            $companyNote->text = $noteText;
            $companyNote->company()->associate($company);
            $companyNote->user()->associate($user);

            $this->connection->transaction(fn() => $companyNote->save());

            $companyNote->unsetRelation('company');
            $this->eventDispatcher->dispatch(new CompanyNoteCreated($companyNote, $company));

        });

    }

    public function updateCompanyNote(CompanyNote $companyNote, string $noteText): CompanyNote
    {
        $violations = $this->validator->validate($noteText, [new NotBlank]);

        count($violations) && throw new ValidationFailedException($noteText, $violations);

        return tap($companyNote, function (CompanyNote $companyNote) use ($noteText) {

            $companyNote->text = $noteText;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_COMPANY_NOTE($companyNote->getKey()),
                10
            );

            $lock->block(30, function () use ($companyNote) {

                $this->connection->transaction(fn() => $companyNote->save());

            });
            
            $this->eventDispatcher->dispatch(new CompanyNoteUpdated($companyNote, $companyNote->company));
        });
    }

    public function deleteCompanyNote(CompanyNote $companyNote): void
    {
        $lock = $this->lockProvider->lock(
            Lock::DELETE_COMPANY_NOTE($companyNote->getKey()),
            10
        );

        $lock->block(30, function () use ($companyNote) {

            $this->connection->transaction(fn() => $companyNote->delete());
            $this->eventDispatcher->dispatch(new CompanyNoteDeleted($companyNote, $companyNote->company));
        });
    }
}