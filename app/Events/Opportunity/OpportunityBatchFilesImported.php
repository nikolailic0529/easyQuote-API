<?php

namespace App\Events\Opportunity;

use Illuminate\Foundation\Events\Dispatchable;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OpportunityBatchFilesImported
{
    use Dispatchable;

    private UploadedFile $opportunitiesDataFile;
    private ?UploadedFile $accountsDataFile;
    private ?UploadedFile $accountContactsFile;

    /**
     * Create a new event instance.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $opportunitiesDataFile
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|null $accountsDataFile
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|null $accountContactsFile
     */
    public function __construct(UploadedFile $opportunitiesDataFile,
                                ?UploadedFile $accountsDataFile,
                                ?UploadedFile $accountContactsFile)
    {
        $this->opportunitiesDataFile = $opportunitiesDataFile;
        $this->accountsDataFile = $accountsDataFile;
        $this->accountContactsFile = $accountContactsFile;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getOpportunitiesDataFile(): UploadedFile
    {
        return $this->opportunitiesDataFile;
    }

    public function getAccountsDataFile(): ?UploadedFile
    {
        return $this->accountsDataFile;
    }

    public function getAccountContactsFile(): ?UploadedFile
    {
        return $this->accountContactsFile;
    }


}
