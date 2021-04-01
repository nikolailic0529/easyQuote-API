<?php

namespace App\Services\WorldwideQuote;

use Illuminate\Database\Eloquent\Model;
use App\Models\{Quote\BaseWorldwideQuote,
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
use App\Services\WorldwideQuote\Models\ReplicatedDistributorQuoteData;
use Illuminate\Database\Eloquent\Collection;
use Webpatser\Uuid\Uuid;

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
     * @return BaseWorldwideQuote
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

        $replicatedVersion = $this->replicateQuoteVersion($activeVersion);

        $replicatedPackAssets = $this->replicatePackQuoteAssets($activeVersion, $replicatedVersion);

        /** @var ReplicatedDistributorQuoteData[] $replicatedDistributorQuotesOfVersion */
        $replicatedDistributorQuotesOfVersion = array_map(
            function (WorldwideDistribution $distributorQuote) use ($replicatedVersion) {
                return $this->replicateDistributorQuote($distributorQuote, $replicatedVersion);
            },
            $activeVersion->worldwideDistributions->all()
        );

        return tap($replicatedVersion, function (WorldwideQuoteVersion $replicatedVersion) use ($activeVersion, $replicatedPackAssets, $replicatedDistributorQuotesOfVersion) {
            $this->persistReplicatedVersionData(
                $replicatedVersion,
                $replicatedPackAssets,
                $replicatedDistributorQuotesOfVersion
            );

            $this->worldwideQuote
                ->activeVersion()
                ->associate($replicatedVersion)
                ->saveOrFail();
        });
    }

    /**
     * @param WorldwideQuoteVersion $version
     * @param WorldwideQuoteAsset[] $replicatedPackAssets
     * @param ReplicatedDistributorQuoteData[] $replicatedDistributorQuotes
     * @throws \Throwable
     */
    protected function persistReplicatedVersionData(WorldwideQuoteVersion $version, array $replicatedPackAssets, array $replicatedDistributorQuotes): void
    {
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

            $groupOfRowBatch = array_merge($groupOfRowBatch, array_map(fn (Model $model) => $model->getAttributes(), $distributorQuoteData->getRowsGroups()));

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

    protected function replicatePackQuoteAssets(WorldwideQuoteVersion $activeVersion, WorldwideQuoteVersion $replicatedVersion): array
    {
        return array_map(function (WorldwideQuoteAsset $asset) use ($replicatedVersion) {
            $newAsset = $asset->replicate(['vendor_short_code']);
            $newAsset->{$newAsset->getKeyName()} = (string)Uuid::generate(4);
            $newAsset->worldwideQuote()->associate($replicatedVersion);
            $newAsset->replicatedAsset()->associate($asset);

            return $newAsset;
        }, $activeVersion->assets->all());
    }

    protected function replicateQuoteVersion(WorldwideQuoteVersion $activeVersion): WorldwideQuoteVersion
    {
        return tap(new WorldwideQuoteVersion(), function (WorldwideQuoteVersion $version) use ($activeVersion) {
            $version->{$version->getKeyName()} = (string)Uuid::generate(4);
            $version->worldwideQuote()->associate($activeVersion->worldwideQuote);
            $version->user()->associate($this->actingUser);
            $version->company_id = $activeVersion->company_id;
            $version->quote_currency_id = $activeVersion->quote_currency_id;
            $version->output_currency_id = $activeVersion->output_currency_id;
            $version->quote_template_id = $activeVersion->quote_template_id;
            $version->exchange_rate_margin = $activeVersion->exchange_rate_margin;
            $version->completeness = $activeVersion->completeness;
            $version->quote_expiry_date = $activeVersion->quote_expiry_date;
            $version->buy_price = $activeVersion->buy_price;
            $version->additional_notes = $activeVersion->additional_notes;
            $version->user_version_sequence_number = $this->resolveNewVersionNumberForActingUser();
            $version->quote_type = $activeVersion->quote_type;
            $version->tax_value = $activeVersion->tax_value;
            $version->margin_value = $activeVersion->margin_value;
            $version->margin_method = $activeVersion->margin_method;
            $version->custom_discount = $activeVersion->custom_discount;
            $version->pricing_document = $activeVersion->pricing_document;
            $version->service_agreement_id = $activeVersion->service_agreement_id;
            $version->system_handle = $activeVersion->system_handle;
            $version->additional_details = $activeVersion->additional_details;
            $version->sort_rows_column = $activeVersion->sort_rows_column;
            $version->sort_rows_direction = $activeVersion->sort_rows_direction;
        });
    }

    protected function resolveNewVersionNumberForActingUser(): int
    {
        $userVersionCount = $this->worldwideQuote->versions()
            ->where((new WorldwideQuoteVersion())->user()->getQualifiedForeignKeyName(), $this->actingUser->getKey())
            ->count();

        return ++$userVersionCount;
    }

    protected function replicateDistributorQuote(WorldwideDistribution $distributorQuote, WorldwideQuoteVersion $replicatedQuoteVersion): ReplicatedDistributorQuoteData
    {
        $replicatedDistributorQuote = $distributorQuote->replicate();

        $replicatedDistributorQuote->{$replicatedDistributorQuote->getKeyName()} = (string)Uuid::generate(4);
        $replicatedDistributorQuote->worldwideQuote()->associate($replicatedQuoteVersion);
        $replicatedDistributorQuote->replicated_distributor_quote_id = $distributorQuote->getKey();

        $replicatedMapping = DistributionFieldColumn::query()
            ->where('worldwide_distribution_id', $distributorQuote->getKey())
            ->get()
            ->keyBy('template_field_id')
            ->all();

        $replicatedMapping = array_map(function (DistributionFieldColumn $fieldColumn) use ($replicatedDistributorQuote) {
            $newFieldColumn = $fieldColumn->replicate();
            $newFieldColumn->worldwideDistribution()->associate($replicatedDistributorQuote);

            return $newFieldColumn;
        }, $replicatedMapping);

        /** @var QuoteFile|null $replicatedDistributorFile */
        $replicatedDistributorFile = transform($distributorQuote->distributorFile, function (QuoteFile $quoteFile) {
            $newQuoteFile = $quoteFile->replicate();
            $newQuoteFile->{$newQuoteFile->getKeyName()} = (string)Uuid::generate(4);
            $newQuoteFile->replicated_quote_file_id = $quoteFile->getKey();
            $newQuoteFile->{$newQuoteFile->getCreatedAtColumn()} = $quoteFile->{$quoteFile->getCreatedAtColumn()};
            $newQuoteFile->{$newQuoteFile->getUpdatedAtColumn()} = $quoteFile->{$quoteFile->getUpdatedAtColumn()};

            return $newQuoteFile;
        });

        $replicatedDistributorQuote->distributorFile()->associate($replicatedDistributorFile);

        /** @var MappedRow[] $replicatedMappedRows */
        $replicatedMappedRows = transform($replicatedDistributorFile, function (QuoteFile $replicatedDistributorFile) use ($distributorQuote) {
            return array_map(function (MappedRow $row) use ($replicatedDistributorFile) {
                $newRow = $row->replicate();
                $newRow->replicated_mapped_row_id = $row->getKey();
                $newRow->{$newRow->getKeyName()} = (string)Uuid::generate(4);
                $newRow->{$newRow->quoteFile()->getForeignKeyName()} = $replicatedDistributorFile->getKey();

                return $newRow;
            }, $distributorQuote->distributorFile->mappedRows->all());
        }, []);

        $replicatedMappedRowsDictionary = (new Collection($replicatedMappedRows))->pluck('id', 'replicated_mapped_row_id')->all();

        $replicatedRowsGroups = array_map(function (DistributionRowsGroup $rowsGroup) use ($replicatedDistributorQuote) {
            $newRowsGroup = $rowsGroup->replicate(['rows_count', 'rows_sum']);
            $newRowsGroup->replicated_rows_group_id = $rowsGroup->getKey();
            $newRowsGroup->{$newRowsGroup->getKeyName()} = (string)Uuid::generate(4);
            $newRowsGroup->{$newRowsGroup->worldwideDistribution()->getForeignKeyName()} = $replicatedDistributorQuote->getKey();
            $newRowsGroup->{$newRowsGroup->getCreatedAtColumn()} = $rowsGroup->{$rowsGroup->getCreatedAtColumn()};
            $newRowsGroup->{$newRowsGroup->getUpdatedAtColumn()} = $rowsGroup->{$rowsGroup->getUpdatedAtColumn()};

            // TODO: update distribution_rows_group_mapped_row basis on replicated_mapped_row_id

            return $newRowsGroup;
        }, $distributorQuote->rowsGroups->all());

        $groupRows = array_map(function (DistributionRowsGroup $rowsGroup) use ($replicatedMappedRowsDictionary) {
            $groupRowKeys = $rowsGroup->replicatedGroupRows()->pluck('id');

            $groupPivotKey = $rowsGroup->rows()->getForeignPivotKeyName();
            $rowPivotKey = $rowsGroup->rows()->getRelatedPivotKeyName();

            return $groupRowKeys->map(fn(string $key) => [
                $groupPivotKey => $rowsGroup->getKey(),
                $rowPivotKey => $replicatedMappedRowsDictionary[$key]
            ])->all();
        }, $replicatedRowsGroups);

        /** @var QuoteFile|null $replicatedScheduleFile */
        $replicatedScheduleFile = transform($distributorQuote->scheduleFile, function (QuoteFile $quoteFile) {

            $newQuoteFile = $quoteFile->replicate();
            $newQuoteFile->{$newQuoteFile->getKeyName()} = (string)Uuid::generate(4);
            $newQuoteFile->replicated_quote_file_id = $quoteFile->getKey();
            $newQuoteFile->{$newQuoteFile->getCreatedAtColumn()} = $quoteFile->{$quoteFile->getCreatedAtColumn()};
            $newQuoteFile->{$newQuoteFile->getUpdatedAtColumn()} = $quoteFile->{$quoteFile->getUpdatedAtColumn()};

            return $newQuoteFile;

        });

        $replicatedDistributorQuote->scheduleFile()->associate($replicatedScheduleFile);

        $replicatedScheduleFileData = transform($distributorQuote->scheduleFile, function (QuoteFile $quoteFile) use ($replicatedScheduleFile) {
            if (is_null($quoteFile->scheduleData)) {
                return null;
            }

            $newScheduleData = $quoteFile->scheduleData->replicate();
            $newScheduleData->{$newScheduleData->getKeyName()} = (string)Uuid::generate(4);
            $newScheduleData->{$newScheduleData->quoteFile()->getForeignKeyName()} = $replicatedScheduleFile->getKey();

            return $newScheduleData;
        });

        $replicatedImportedRows = transform($distributorQuote->distributorFile, function (QuoteFile $quoteFile) use ($replicatedDistributorFile) {
                return array_map(function (ImportedRow $row) use ($replicatedDistributorFile) {
                    $newRow = $row->replicate();
                    $newRow->{$newRow->getKeyName()} = (string)Uuid::generate(4);
                    $newRow->quoteFile()->associate($replicatedDistributorFile);

                    return $newRow;
                }, $quoteFile->rowsData->all());
            }) ?? [];

        return new ReplicatedDistributorQuoteData(
            $replicatedDistributorQuote,
            $replicatedMapping,
            $replicatedRowsGroups,
            $groupRows,
            $replicatedMappedRows,
            $replicatedDistributorFile,
            $replicatedImportedRows,
            $replicatedScheduleFile,
            $replicatedScheduleFileData
        );
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
