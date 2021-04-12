<?php

namespace App\Services\WorldwideQuote;

use App\Models\{Quote\DistributionFieldColumn,
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
use App\Services\WorldwideQuote\Models\ReplicatedVersionData;
use Illuminate\Database\Eloquent\Model;

class WorldwideQuoteVersionGuard
{
    protected WorldwideQuote $worldwideQuote;

    protected User $actingUser;

    protected static string $activeVersionRelationKey = 'activeVersion';

    /**
     * WorldwideQuoteVersionGuard constructor.
     * @param WorldwideQuote $worldwideQuote
     * @param User $actingUser
     */
    public function __construct(WorldwideQuote $worldwideQuote, User $actingUser)
    {
        $this->worldwideQuote = $worldwideQuote;
        $this->actingUser = $actingUser;
    }


    /**
     * When the acting user is matching to an owner of the active quote version,
     * simply return the active version of the quote entity.
     *
     * When the acting user is not not owner of the active quote version,
     * perform versioning, set a new active version, and return the active version of the quote entity.
     *
     * @return WorldwideQuoteVersion
     * @throws \Throwable
     */
    public function resolveModelForActingUser(): WorldwideQuoteVersion
    {
        if ($this->isActingUserOwnerOfActiveModelVersion()) {
            return $this->getActiveVersionOfModel();
        }

        return $this->performQuoteVersioning();
    }

    /**
     * @return WorldwideQuoteVersion
     * @throws \Throwable
     */
    protected function performQuoteVersioning(): WorldwideQuoteVersion
    {
        $activeVersion = $this->getActiveVersionOfModel();

        $activeVersion->refresh();

        $replicatedVersionData = (new WorldwideQuoteReplicator())
            ->getReplicatedVersionData(
                $activeVersion
            );

        return tap($replicatedVersionData->getReplicatedVersion(), function (WorldwideQuoteVersion $replicatedVersion) use ($replicatedVersionData) {
            $this->persistReplicatedVersionData($replicatedVersionData);

            $this->worldwideQuote
                ->activeVersion()
                ->associate($replicatedVersion)
                ->saveOrFail();
        });
    }

    /**
     * @param ReplicatedVersionData $replicatedVersionData
     * @throws \Throwable
     */
    protected function persistReplicatedVersionData(ReplicatedVersionData $replicatedVersionData): void
    {
        $version = $replicatedVersionData->getReplicatedVersion();
        $replicatedPackAssets = $replicatedVersionData->getReplicatedPackAssets();
        $replicatedDistributorQuotes = $replicatedVersionData->getReplicatedDistributorQuotes();

        $version->worldwideQuote()->associate($this->worldwideQuote);
        $version->unsetRelation('worldwideQuote');
        $version->user()->associate($this->actingUser);
        $version->user_version_sequence_number = $this->resolveNewVersionNumberForActingUser();

        $distributorQuoteBatch = [];
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

        $connection = $version->getConnection();

        $connection->transaction(function () use (
            $distributorQuoteBatch,
            $distributorFileBatch,
            $mappingBatch,
            $importedRowBatch,
            $scheduleFileBatch,
            $scheduleFileDataBatch,
            $packAssetBatch,
            $mappedRowBatch,
            $groupOfRowBatch,
            $rowOfGroupBatch,
            $connection,
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
                $connection->table((new DistributionRowsGroup())->rows()->getTable())
                    ->insert($rowOfGroupBatch);
            }
        });
    }

    protected function resolveNewVersionNumberForActingUser(): int
    {
        $userVersionCount = $this->worldwideQuote->versions()
            ->where((new WorldwideQuoteVersion())->user()->getQualifiedForeignKeyName(), $this->actingUser->getKey())
            ->max('user_version_sequence_number');

        return ++$userVersionCount;
    }

    public function getActiveVersionOfModel(): WorldwideQuoteVersion
    {
        return $this->worldwideQuote->getRelationValue(static::$activeVersionRelationKey);
    }

    public function isActingUserOwnerOfBaseModel(): bool
    {
        return $this->worldwideQuote->{$this->worldwideQuote->user()->getForeignKeyName()} ===
            $this->actingUser->getKey();
    }

    public function isActingUserOwnerOfActiveModelVersion(): bool
    {
        $activeVersion = $this->getActiveVersionOfModel();

        return
            $activeVersion->{$activeVersion->user()->getForeignKeyName()} ===
            $this->actingUser->getKey();
    }
}
