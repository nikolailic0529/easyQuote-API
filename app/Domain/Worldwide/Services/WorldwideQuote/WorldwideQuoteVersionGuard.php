<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\Note\Models\Note;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Events\Quote\NewVersionOfWorldwideQuoteCreated;
use App\Domain\Worldwide\Models\DistributionFieldColumn;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Domain\Worldwide\Services\WorldwideQuote\Models\ReplicatedVersionData;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class WorldwideQuoteVersionGuard
{
    protected static string $activeVersionRelationKey = 'activeVersion';

    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly EventDispatcher $eventDispatcher
    ) {
    }

    /**
     * When the acting user is matching to an owner of the active quote version,
     * simply return the active version of the quote entity.
     *
     * When the acting user is not owner of the active quote version,
     * perform versioning, set a new active version, and return the active version of the quote entity.
     *
     * @throws \Throwable
     */
    public function resolveModelForActingUser(WorldwideQuote $worldwideQuote, User $actingUser): WorldwideQuoteVersion
    {
        if ($this->isActingUserOwnerOfActiveModelVersion($worldwideQuote, $actingUser)) {
            return $this->getActiveVersionOfModel($worldwideQuote);
        }

        return $this->performQuoteVersioning($worldwideQuote, $actingUser);
    }

    /**
     * @throws \Throwable
     */
    public function performQuoteVersioning(WorldwideQuote $worldwideQuote, User $actingUser): WorldwideQuoteVersion
    {
        $originalActiveVersion = $this->getActiveVersionOfModel($worldwideQuote);

        return $this->performQuoteVersioningFromVersion($worldwideQuote, $originalActiveVersion, $actingUser);
    }

    public function performQuoteVersioningFromVersion(
        WorldwideQuote $worldwideQuote,
        WorldwideQuoteVersion $quoteVersion,
        User $actingUser
    ): WorldwideQuoteVersion {
        $quoteVersion->refresh();

        $replicatedVersionData = (new WorldwideQuoteReplicator())
            ->getReplicatedVersionData(
                $quoteVersion
            );

        $replicatedVersion = tap($replicatedVersionData->getReplicatedVersion(),
            function (WorldwideQuoteVersion $version) use ($worldwideQuote, $actingUser) {
                $version->worldwideQuote()->associate($worldwideQuote);
                $version->unsetRelation('worldwideQuote');
                $version->user()->associate($actingUser);
                $version->user_version_sequence_number = $this->resolveNewVersionNumberForUser(
                    $worldwideQuote,
                    $actingUser->getKey()
                );
            });

        transform($replicatedVersionData->getReplicatedQuoteNote(), function (Note $note) use ($actingUser) {
            $note->owner()->associate($actingUser);
        });

        return tap($replicatedVersion, function (WorldwideQuoteVersion $replicatedVersion) use (
            $quoteVersion,
            $actingUser,
            $worldwideQuote,
            $replicatedVersionData
        ) {
            $this->persistReplicatedVersionData($replicatedVersionData);

            $this->associateActiveVersionToModel($worldwideQuote, $replicatedVersion);

            $this->eventDispatcher->dispatch(
                new NewVersionOfWorldwideQuoteCreated(
                    $quoteVersion,
                    $replicatedVersion,
                    $actingUser
                )
            );
        });
    }

    protected function associateActiveVersionToModel(
        WorldwideQuote $worldwideQuote,
        WorldwideQuoteVersion $version
    ): void {
        $worldwideQuote
            ->activeVersion()
            ->associate($version);

        $this->connection->transaction(fn () => $worldwideQuote->save());
    }

    /**
     * @throws \Throwable
     */
    public function persistReplicatedVersionData(ReplicatedVersionData $replicatedVersionData): void
    {
        $version = $replicatedVersionData->getReplicatedVersion();
        $quoteNote = $replicatedVersionData->getReplicatedQuoteNote();
        $replicatedPackAssets = $replicatedVersionData->getReplicatedPackAssets();
        $replicatedPackAssetsGroups = $replicatedVersionData->getReplicatedAssetsGroups();
        $replicatedPackAssetsOfGroups = $replicatedVersionData->getReplicatedAssetsOfGroups();
        $replicatedDistributorQuotes = $replicatedVersionData->getReplicatedDistributorQuotes();

        $versionAddressPivots = $replicatedVersionData->getAddressPivots();
        $versionContactPivots = $replicatedVersionData->getContactPivots();

        $distributorQuoteBatch = [];
        $distributorVendorPivotBatch = [];
        $distributorAddressPivotBatch = [];
        $distributorContactPivotBatch = [];
        $mappingBatch = [];
        $distributorFileBatch = [];
        $scheduleFileBatch = [];
        $scheduleFileDataBatch = [];
        $importedRowBatch = [];
        $groupOfRowBatch = [];
        $rowOfGroupBatch = [];
        $mappedRowBatch = [];
        $packAssetBatch = array_map(fn (WorldwideQuoteAsset $asset) => $asset->getAttributes(), $replicatedPackAssets);
        $packAssetsGroupBatch = array_map(fn (WorldwideQuoteAssetsGroup $assetsGroup) => $assetsGroup->getAttributes(),
            $replicatedPackAssetsGroups);

        foreach ($replicatedDistributorQuotes as $distributorQuoteData) {
            $distributorQuoteBatch[] = $distributorQuoteData->getDistributorQuote()->getAttributes();

            $distributorVendorPivotBatch = array_merge($distributorVendorPivotBatch,
                $distributorQuoteData->getVendorPivots());

            $distributorAddressPivotBatch = array_merge($distributorAddressPivotBatch,
                $distributorQuoteData->getAddressPivots());

            $distributorContactPivotBatch = array_merge($distributorContactPivotBatch,
                $distributorQuoteData->getContactPivots());

            $distributorMapping = array_map(fn (DistributionFieldColumn $fieldColumn) => $fieldColumn->getAttributes(),
                array_values($distributorQuoteData->getMapping()));

            $mappingBatch = array_merge($mappingBatch, $distributorMapping);

            $importedRowBatch = array_merge($importedRowBatch,
                array_map(fn (ImportedRow $row) => $row->getAttributes(), $distributorQuoteData->getImportedRows()));

            $distributorFile = $distributorQuoteData->getDistributorFile();

            $mappedRowBatch = array_merge($mappedRowBatch,
                array_map(fn (MappedRow $row) => $row->getAttributes(), $distributorQuoteData->getMappedRows()));

            $groupOfRowBatch = array_merge($groupOfRowBatch,
                array_map(fn (Model $model) => $model->getAttributes(), $distributorQuoteData->getRowsGroups()));

            $rowOfGroupBatch = array_merge($rowOfGroupBatch, array_merge([], ...$distributorQuoteData->getGroupRows()));

            if (!is_null($distributorFile)) {
                $distributorFileBatch[] = $distributorFile->getAttributes();
            }

            $scheduleFile = $distributorQuoteData->getScheduleFile();

            if (!is_null($scheduleFile)) {
                $scheduleFileBatch[] = $scheduleFile->getAttributes();
            }

            $scheduleFileData = $distributorQuoteData->getScheduleData();

            if (!is_null($scheduleFileData)) {
                $scheduleFileDataBatch[] = $scheduleFileData->getAttributes();
            }
        }

        $this->connection->transaction(function () use (
            $versionContactPivots,
            $versionAddressPivots,
            $distributorQuoteBatch,
            $distributorVendorPivotBatch,
            $distributorAddressPivotBatch,
            $distributorContactPivotBatch,
            $distributorFileBatch,
            $mappingBatch,
            $importedRowBatch,
            $scheduleFileBatch,
            $scheduleFileDataBatch,
            $packAssetBatch,
            $mappedRowBatch,
            $groupOfRowBatch,
            $rowOfGroupBatch,
            $version,
            $quoteNote,
            $packAssetsGroupBatch,
            $replicatedPackAssetsOfGroups
        ) {
            $version->save();

            if (!is_null($quoteNote)) {
                $quoteNote->save();

                $quoteNote->worldwideQuotesHaveNote()->attach($version->worldwideQuote);
                $quoteNote->worldwideQuoteVersionsHaveNote()->attach($version);
            }

            if (!empty($versionAddressPivots)) {
                $this->connection->table($version->addresses()->getTable())
                    ->insert($versionAddressPivots);
            }

            if (!empty($versionContactPivots)) {
                $this->connection->table($version->contacts()->getTable())
                    ->insert($versionContactPivots);
            }

            if (!empty($distributorFileBatch)) {
                QuoteFile::query()->insert($distributorFileBatch);
            }

            if (!empty($scheduleFileBatch)) {
                QuoteFile::query()->insert($scheduleFileBatch);
            }

            if (!empty($distributorQuoteBatch)) {
                WorldwideDistribution::query()->insert($distributorQuoteBatch);
            }

            if (!empty($distributorVendorPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->vendors()->getTable())
                    ->insert($distributorVendorPivotBatch);
            }

            if (!empty($distributorAddressPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->addresses()->getTable())
                    ->insert($distributorAddressPivotBatch);
            }

            if (!empty($distributorContactPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->contacts()->getTable())
                    ->insert($distributorContactPivotBatch);
            }

            if (!empty($mappingBatch)) {
                DistributionFieldColumn::query()->insert($mappingBatch);
            }

            if (!empty($importedRowBatch)) {
                ImportedRow::query()->insert($importedRowBatch);
            }

            if (!empty($scheduleFileDataBatch)) {
                ScheduleData::query()->insert($scheduleFileDataBatch);
            }

            if (!empty($packAssetBatch)) {
                WorldwideQuoteAsset::query()->insert($packAssetBatch);
            }

            if (!empty($packAssetsGroupBatch)) {
                $this->connection->table((new WorldwideQuoteAssetsGroup())->getTable())
                    ->insert($packAssetsGroupBatch);
            }

            if (!empty($replicatedPackAssetsOfGroups)) {
                $this->connection->table((new WorldwideQuoteAssetsGroup())->assets()->getTable())
                    ->insert($replicatedPackAssetsOfGroups);
            }

            if (!empty($mappedRowBatch)) {
                MappedRow::query()->insert($mappedRowBatch);
            }

            if (!empty($groupOfRowBatch)) {
                DistributionRowsGroup::query()->insert($groupOfRowBatch);
            }

            if (!empty($rowOfGroupBatch)) {
                $this->connection->table((new DistributionRowsGroup())->rows()->getTable())
                    ->insert($rowOfGroupBatch);
            }
        });
    }

    public function resolveNewVersionNumberForUser(WorldwideQuote $worldwideQuote, string $userId): int
    {
        $userVersionCount = $worldwideQuote->versions()
            ->where((new WorldwideQuoteVersion())->user()->getQualifiedForeignKeyName(), $userId)
            ->max('user_version_sequence_number');

        return ++$userVersionCount;
    }

    public function getActiveVersionOfModel(WorldwideQuote $worldwideQuote): WorldwideQuoteVersion
    {
        return $worldwideQuote->getRelationValue(static::$activeVersionRelationKey);
    }

    public function isActingUserOwnerOfActiveModelVersion(WorldwideQuote $worldwideQuote, User $actingUser): bool
    {
        $activeVersion = $this->getActiveVersionOfModel($worldwideQuote);

        return
            $activeVersion->{$activeVersion->user()->getForeignKeyName()} ===
            $actingUser->getKey();
    }
}
