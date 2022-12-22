<?php

namespace App\Services\DataAllocation;

use App\Contracts\CauserAware;
use App\DTO\DataAllocation\AssignToUserData;
use App\DTO\DataAllocation\SelectAllocationRecordData;
use App\DTO\DataAllocation\Stages\ImportStageData;
use App\DTO\DataAllocation\Stages\InitStageData;
use App\DTO\DataAllocation\Stages\ResultsStageData;
use App\DTO\DataAllocation\Stages\ReviewStageData;
use App\DTO\Opportunity\ImportFilesData;
use App\Enum\DataAllocationRecordResultEnum;
use App\Enum\DataAllocationStageEnum;
use App\Models\DataAllocation\DataAllocation;
use App\Models\DataAllocation\DataAllocationFile;
use App\Models\DataAllocation\DataAllocationRecord;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Opportunity\ImportedOpportunityDataValidator;
use App\Services\Opportunity\OpportunityImportService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DataAllocationEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionResolverInterface $connectionResolver,
        protected OpportunityImportService $opportunityImportService,
        protected Filesystem $filesystem,
    ) {
    }

    public function processInitStage(InitStageData $data): DataAllocation
    {
        return tap(new DataAllocation(), function (DataAllocation $allocation): void {
            if ($this->causer instanceof User) {
                $allocation->owner()->associate($this->causer);
            }

            $allocation->stage = DataAllocationStageEnum::Init;

            $this->connectionResolver->connection()->transaction(static fn() => $allocation->save());
        });
    }

    public function processImportStage(DataAllocation $allocation, ImportStageData $data): DataAllocation
    {
        return tap($allocation, function (DataAllocation $allocation) use ($data): void {
            $allocation->forceFill($data->except('assigned_users')->all());
            $allocation->stage = DataAllocationStageEnum::Import;

            /** @var BaseCollection $assignedUserPivots */
            $assignedUserPivots = $data->assigned_users->toCollection()
                ->mapWithKeys(static function (AssignToUserData $data): array {
                    static $order = 0;

                    return [
                        $data->id => ['entity_order' => $order++],
                    ];
                });

            $this->connectionResolver->connection()
                ->transaction(static function () use ($allocation, $assignedUserPivots) {
                    $allocation->save();
                    $allocation->assignedUsers()->sync($assignedUserPivots);
                });

            $this->performImportOfFile($allocation->refresh()->file);
            $this->performDataDistribution($allocation);
        });
    }

    protected function performImportOfFile(DataAllocationFile $file): void
    {
        if (null !== $file->imported_at) {
            return;
        }

        ImportedOpportunityDataValidator::setFlag(ImportedOpportunityDataValidator::IGNORE_MISSING_ACC_DATA, true);

        $result = $this->opportunityImportService->import(
            new ImportFilesData(
                opportunities_file: new UploadedFile(
                    path: $this->filesystem->path($file->filepath),
                    originalName: $file->filename,
                    test: true,
                )
            )
        );

        $records = collect($result->opportunities)
            ->map(static function (Opportunity $opportunity) use ($file) {
                static $order = -1;

                $order++;

                return tap(new DataAllocationRecord(),
                    static function (DataAllocationRecord $record) use ($order, $opportunity, $file): void {
                        $record->file()->associate($file);
                        $record->opportunity()->associate($opportunity);
                        $record->entity_order = $order;
                        $record->is_selected = true;
                    });
            });

        $this->connectionResolver->connection()->transaction(
            static function () use ($file, $records): void {
                $records->each->save();

                $file->imported_at = now();
                $file->save();
            });
    }

    protected function performDataDistribution(DataAllocation $allocation): void
    {
        $users = $allocation->assignedUsers;
        $usersCount = $users->count();

        // Currently, only `evenly` algorithm is available.
        $allocation->file->allocationRecords
            ->each(
                static function (DataAllocationRecord $record, int $i) use ($usersCount, $users) {
                    $record->assignedUser()->associate($users[$i % $usersCount]);
                }
            );

        $this->connectionResolver->connection()->transaction(static function () use ($allocation): void {
            $allocation->file->allocationRecords->each->save();
        });
    }

    public function processReviewStage(DataAllocation $allocation, ReviewStageData $data)
    {
        return tap($allocation, function (DataAllocation $allocation) use ($data): void {
            $allocation->stage = DataAllocationStageEnum::Review;

            $selectedMap = $data->selected_records->toCollection()->mapWithKeys(static function (
                SelectAllocationRecordData $recordData
            ): array {
                return [$recordData->id => true];
            });

            $assignUserToModelRelations = Collection::make();

            $allocation->file->allocationRecords->each(
                function (DataAllocationRecord $record) use ($assignUserToModelRelations, $selectedMap): void {
                    if ($selectedMap->has($record->getKey())) {
                        $opportunity = $this->opportunityImportService->performOpportunitySave($record->opportunity);
                        $record->result = $opportunity->wasRecentlyCreated
                            ? DataAllocationRecordResultEnum::NewRecord
                            : DataAllocationRecordResultEnum::UpdatedRecord;
                        $record->is_selected = true;

                        $assignUserToModelRelations[] = $record;
                    } else {
                        $record->result = DataAllocationRecordResultEnum::Unprocessed;
                        $record->is_selected = false;
                    }
                }
            );

            $this->connectionResolver->connection()->transaction(static function () use (
                $assignUserToModelRelations,
                $allocation
            ): void {
                $allocation->file->allocationRecords->each->save();

                foreach ($assignUserToModelRelations as $record) {
                    $record->opportunity->assignedToUsers()->syncWithoutDetaching([
                        $record->assignedUser->getKey() => [
                            'assignment_start_date' => $allocation->assignment_start_date,
                            'assignment_end_date' => $allocation->assignment_end_date,
                        ],
                    ]);
                }

                $allocation->save();
            });
        });
    }

    public function processResultsStage(DataAllocation $allocation, ResultsStageData $data)
    {
        return tap($allocation, function (DataAllocation $allocation) use ($data): void {
            $allocation->stage = DataAllocationStageEnum::Results;

            $this->connectionResolver->connection()->transaction(static function () use ($allocation): void {
                $allocation->save();
            });
        });
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }

    public function deleteDataAllocation(DataAllocation $allocation)
    {
        $this->connectionResolver->connection()
            ->transaction(static fn() => $allocation->delete());
    }
}