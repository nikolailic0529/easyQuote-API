<?php

namespace App\Services\WorldwideQuote;

use App\Models\{Address,
    Contact,
    Quote\DistributionFieldColumn,
    Quote\WorldwideDistribution,
    Quote\WorldwideQuote,
    Quote\WorldwideQuoteVersion,
    QuoteFile\DistributionRowsGroup,
    QuoteFile\ImportedRow,
    QuoteFile\MappedRow,
    QuoteFile\QuoteFile,
    QuoteFile\ScheduleData,
    User,
    WorldwideQuoteAsset};
use App\Events\WorldwideQuote\NewVersionOfWorldwideQuoteCreated;
use App\Services\WorldwideQuote\Models\ReplicatedVersionData;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class WorldwideQuoteVersionGuard
{
    protected ConnectionInterface $connection;

    protected EventDispatcher $eventDispatcher;

    protected static string $activeVersionRelationKey = 'activeVersion';

    /**
     * WorldwideQuoteVersionGuard constructor.
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Illuminate\Contracts\Events\Dispatcher $eventDispatcher
     */
    public function __construct(ConnectionInterface $connection, EventDispatcher $eventDispatcher)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * When the acting user is matching to an owner of the active quote version,
     * simply return the active version of the quote entity.
     *
     * When the acting user is not not owner of the active quote version,
     * perform versioning, set a new active version, and return the active version of the quote entity.
     *
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Models\User $actingUser
     * @return WorldwideQuoteVersion
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
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Models\User $actingUser
     * @return WorldwideQuoteVersion
     * @throws \Throwable
     */
    protected function performQuoteVersioning(WorldwideQuote $worldwideQuote, User $actingUser): WorldwideQuoteVersion
    {
        $originalActiveVersion = $this->getActiveVersionOfModel($worldwideQuote);

        $originalActiveVersion->refresh();

        $replicatedVersionData = (new WorldwideQuoteReplicator())
            ->getReplicatedVersionData(
                $originalActiveVersion
            );

        return tap($replicatedVersionData->getReplicatedVersion(), function (WorldwideQuoteVersion $replicatedVersion) use ($originalActiveVersion, $actingUser, $worldwideQuote, $replicatedVersionData) {
            $this->persistReplicatedVersionData($replicatedVersionData, $worldwideQuote, $actingUser);

            $this->associateActiveVersionToModel($worldwideQuote, $replicatedVersion);

            $this->eventDispatcher->dispatch(
                new NewVersionOfWorldwideQuoteCreated(
                    $originalActiveVersion,
                    $replicatedVersion
                )
            );
        });
    }

    protected function associateActiveVersionToModel(WorldwideQuote $worldwideQuote, WorldwideQuoteVersion $version): void
    {
        $worldwideQuote
            ->activeVersion()
            ->associate($version);

        $this->connection->transaction(fn() => $worldwideQuote->save());
    }

    /**
     * @param ReplicatedVersionData $replicatedVersionData
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Models\User $actingUser
     * @throws \Throwable
     */
    protected function persistReplicatedVersionData(ReplicatedVersionData $replicatedVersionData, WorldwideQuote $worldwideQuote, User $actingUser): void
    {
        $version = $replicatedVersionData->getReplicatedVersion();
        $replicatedPackAssets = $replicatedVersionData->getReplicatedPackAssets();
        $replicatedDistributorQuotes = $replicatedVersionData->getReplicatedDistributorQuotes();

        $version->worldwideQuote()->associate($worldwideQuote);
        $version->unsetRelation('worldwideQuote');
        $version->user()->associate($actingUser);
        $version->user_version_sequence_number = $this->resolveNewVersionNumberForActingUser($worldwideQuote, $actingUser);

        $distributorQuoteBatch = [];
        $addressDataBatch = [];
        $addressPivotBatch = [];
        $contactDataBatch = [];
        $contactPivotBatch = [];
        $mappingBatch = [];
        $distributorFileBatch = [];
        $scheduleFileBatch = [];
        $scheduleFileDataBatch = [];
        $importedRowBatch = [];
        $groupOfRowBatch = [];
        $rowOfGroupBatch = [];
        $mappedRowBatch = [];
        $packAssetBatch = array_map(fn(WorldwideQuoteAsset $asset) => $asset->getAttributes(), $replicatedPackAssets);

        foreach ($replicatedDistributorQuotes as $distributorQuoteData) {
            $distributorQuoteBatch[] = $distributorQuoteData->getDistributorQuote()->getAttributes();

            $addressDataBatch = array_merge($addressDataBatch, array_map(fn(Address $address) => $address->getAttributes(), $distributorQuoteData->getReplicatedAddressesData()->getAddressModels()));
            $addressPivotBatch = array_merge($addressPivotBatch, $distributorQuoteData->getReplicatedAddressesData()->getAddressPivots());

            $contactDataBatch = array_merge($contactDataBatch, array_map(fn(Contact $contact) => $contact->getAttributes(), $distributorQuoteData->getReplicatedContactsData()->getContactModels()));
            $contactPivotBatch = array_merge($contactPivotBatch, $distributorQuoteData->getReplicatedContactsData()->getContactPivots());


            $distributorMapping = array_map(fn(DistributionFieldColumn $fieldColumn) => $fieldColumn->getAttributes(), $distributorQuoteData->getMapping());

            $mappingBatch = array_merge($mappingBatch, $distributorMapping);

            $importedRowBatch = array_merge($importedRowBatch, array_map(fn(ImportedRow $row) => $row->getAttributes(), $distributorQuoteData->getImportedRows()));

            $distributorFile = $distributorQuoteData->getDistributorFile();

            $mappedRowBatch = array_merge($mappedRowBatch, array_map(fn(MappedRow $row) => $row->getAttributes(), $distributorQuoteData->getMappedRows()));

            $groupOfRowBatch = array_merge($groupOfRowBatch, array_map(fn(Model $model) => $model->getAttributes(), $distributorQuoteData->getRowsGroups()));

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
            $distributorQuoteBatch,
            $addressDataBatch,
            $addressPivotBatch,
            $contactDataBatch,
            $contactPivotBatch,
            $distributorFileBatch,
            $mappingBatch,
            $importedRowBatch,
            $scheduleFileBatch,
            $scheduleFileDataBatch,
            $packAssetBatch,
            $mappedRowBatch,
            $groupOfRowBatch,
            $rowOfGroupBatch,
            $version
        ) {
            $version->save();

            if (!empty($distributorFileBatch)) {
                QuoteFile::query()->insert($distributorFileBatch);
            }

            if (!empty($scheduleFileBatch)) {
                QuoteFile::query()->insert($scheduleFileBatch);
            }

            if (!empty($distributorQuoteBatch)) {
                WorldwideDistribution::query()->insert($distributorQuoteBatch);
            }

            if (!empty($addressDataBatch)) {
                Address::query()->insert($addressDataBatch);
            }

            if (!empty($addressPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->addresses()->getTable())
                    ->insert($addressPivotBatch);
            }

            if (!empty($contactDataBatch)) {
                Contact::query()->insert($contactDataBatch);
            }

            if (!empty($contactPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->contacts()->getTable())
                    ->insert($contactPivotBatch);
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

    protected function resolveNewVersionNumberForActingUser(WorldwideQuote $worldwideQuote, User $actingUser): int
    {
        $userVersionCount = $worldwideQuote->versions()
            ->where((new WorldwideQuoteVersion())->user()->getQualifiedForeignKeyName(), $actingUser->getKey())
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
