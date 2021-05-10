<?php

namespace App\Services\WorldwideQuote;

use App\Models\Address;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\WorldwideQuoteAsset;
use App\Services\WorldwideQuote\Models\ReplicatedAddressesData;
use App\Services\WorldwideQuote\Models\ReplicatedContactsData;
use App\Services\WorldwideQuote\Models\ReplicatedDistributorQuoteData;
use App\Services\WorldwideQuote\Models\ReplicatedVersionData;
use Illuminate\Database\Eloquent\Collection;
use Webpatser\Uuid\Uuid;

class WorldwideQuoteReplicator
{
    public function getReplicatedVersionData(WorldwideQuoteVersion $version): ReplicatedVersionData
    {
        $replicatedVersion = $this->replicateQuoteVersion($version);

        $versionAddressKeys = $this->getAddressPivotsFromVersion($version, $replicatedVersion);

        $versionContactKeys = $this->getContactPivotsFromVersion($version, $replicatedVersion);

        $replicatedPackAssets = $this->replicatePackQuoteAssets($version, $replicatedVersion);

        $replicatedDistributorQuotes = $this->replicateDistributorQuotesOfVersion($version, $replicatedVersion);

        return new ReplicatedVersionData(
            $replicatedVersion,
            $versionAddressKeys,
            $versionContactKeys,
            $replicatedPackAssets,
            $replicatedDistributorQuotes
        );
    }

    protected function getAddressPivotsFromVersion(WorldwideQuoteVersion $activeVersion, WorldwideQuoteVersion $replicatedVersion): array
    {
        $addressKeys = $activeVersion->addresses()->pluck($activeVersion->addresses()->getQualifiedRelatedKeyName())->all();
        $addressesRelation = $replicatedVersion->addresses();

        return array_map(function (string $addressKey) use ($replicatedVersion, $addressesRelation) {
            return [
                $addressesRelation->getRelatedPivotKeyName() => $addressKey,
                $addressesRelation->getForeignPivotKeyName() => $replicatedVersion->getKey(),
            ];
        }, $addressKeys);
    }

    protected function getContactPivotsFromVersion(WorldwideQuoteVersion $activeVersion, WorldwideQuoteVersion $replicatedVersion): array
    {
        $contactKeys = $activeVersion->contacts()->pluck($activeVersion->contacts()->getQualifiedRelatedKeyName())->all();
        $contactsRelation = $replicatedVersion->contacts();

        return array_map(function (string $contactKey) use ($replicatedVersion, $contactsRelation) {
            return [
                $contactsRelation->getRelatedPivotKeyName() => $contactKey,
                $contactsRelation->getForeignPivotKeyName() => $replicatedVersion->getKey(),
            ];
        }, $contactKeys);
    }

    protected function replicateQuoteVersion(WorldwideQuoteVersion $activeVersion): WorldwideQuoteVersion
    {
        return tap(new WorldwideQuoteVersion(), function (WorldwideQuoteVersion $version) use ($activeVersion) {
            $version->{$version->getKeyName()} = (string)Uuid::generate(4);
//            $version->worldwideQuote()->associate($activeVersion->worldwideQuote);
//            $version->user()->associate($this->actingUser);
            $version->company_id = $activeVersion->company_id;
            $version->quote_currency_id = $activeVersion->quote_currency_id;
            $version->output_currency_id = $activeVersion->output_currency_id;
            $version->quote_template_id = $activeVersion->quote_template_id;
            $version->exchange_rate_margin = $activeVersion->exchange_rate_margin;
            $version->completeness = $activeVersion->completeness;
            $version->quote_expiry_date = $activeVersion->quote_expiry_date;
            $version->buy_price = $activeVersion->buy_price;
            $version->additional_notes = $activeVersion->additional_notes;
            $version->user_version_sequence_number = null;
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

    protected function replicateDistributorQuotesOfVersion(WorldwideQuoteVersion $activeVersion, WorldwideQuoteVersion $replicatedQuoteVersion): array
    {
        return array_map(
            function (WorldwideDistribution $distributorQuote) use ($replicatedQuoteVersion) {
                return $this->replicateDistributorQuote($distributorQuote, $replicatedQuoteVersion);
            },
            $activeVersion->worldwideDistributions->all()
        );
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

            return $newRowsGroup;
        }, $distributorQuote->rowsGroups->all());

        $groupRows = array_map(function (DistributionRowsGroup $rowsGroup) use ($replicatedMappedRowsDictionary) {
            $groupRowKeys = $rowsGroup->replicatedGroupRows()->pluck('id');

            $groupPivotKey = $rowsGroup->rows()->getForeignPivotKeyName();
            $rowPivotKey = $rowsGroup->rows()->getRelatedPivotKeyName();

            $rowsOfGroup = [];

            foreach ($groupRowKeys as $key) {
                if (isset($replicatedMappedRowsDictionary[$key])) {
                    $rowsOfGroup[] = [
                        $groupPivotKey => $rowsGroup->getKey(),
                        $rowPivotKey => $replicatedMappedRowsDictionary[$key]
                    ];
                }
            }

            return $rowsOfGroup;
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

        $addressPivots = $this->getAddressPivotsFromDistributorQuote($distributorQuote, $replicatedDistributorQuote);
        $contactPivots = $this->getContactPivotsFromDistributorQuote($distributorQuote, $replicatedDistributorQuote);

        return new ReplicatedDistributorQuoteData(
            $replicatedDistributorQuote,
            $addressPivots,
            $contactPivots,
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

    protected function getAddressPivotsFromDistributorQuote(WorldwideDistribution $distributorQuote, WorldwideDistribution $replicatedDistributorQuote): array
    {
        $addressKeys = $distributorQuote->addresses()->pluck($distributorQuote->addresses()->getQualifiedRelatedKeyName())->all();
        $addressesRelation = $replicatedDistributorQuote->addresses();

        return array_map(function (string $addressKey) use ($addressesRelation, $replicatedDistributorQuote) {
            return [
                $addressesRelation->getRelatedPivotKeyName() => $addressKey,
                $addressesRelation->getForeignPivotKeyName() => $replicatedDistributorQuote->getKey(),
            ];
        }, $addressKeys);
    }

    protected function getContactPivotsFromDistributorQuote(WorldwideDistribution $distributorQuote, WorldwideDistribution $replicatedDistributorQuote): array
    {
        $contactKeys =  $distributorQuote->contacts()->pluck($distributorQuote->contacts()->getQualifiedRelatedKeyName())->all();
        $contactsRelation = $replicatedDistributorQuote->contacts();

        return array_map(function (string $contactKey) use ($contactsRelation, $replicatedDistributorQuote) {
            return [
                $contactsRelation->getRelatedPivotKeyName() => $contactKey,
                $contactsRelation->getForeignPivotKeyName() => $replicatedDistributorQuote->getKey(),
            ];
        }, $contactKeys);
    }

    private function replicateAddressModelsOfDistributorQuote(WorldwideDistribution $originalDistributorQuote, WorldwideDistribution $replicatedDistributorQuote): ReplicatedAddressesData
    {
        $newAddressModels = [];
        $newAddressPivots = [];

        foreach ($originalDistributorQuote->addresses as $address) {
            $newAddress = $address->replicate();
            $newAddress->{$newAddress->getKeyName()} = (string)Uuid::generate(4);
            $newAddress->{$newAddress->getCreatedAtColumn()} = $newAddress->freshTimestampString();
            $newAddress->{$newAddress->getUpdatedAtColumn()} = $newAddress->freshTimestampString();

            $newAddressModels[] = $newAddress;
            $newAddressPivots[] = [
                'address_id' => $newAddress->getKey(),
                'replicated_address_id' => $address->getKey(),
                'worldwide_distribution_id' => $replicatedDistributorQuote->getKey(),
                'is_default' => $address->pivot->is_default
            ];
        }

        return new ReplicatedAddressesData($newAddressModels, $newAddressPivots);
    }

    private function replicateContactModelsOfDistributorQuote(WorldwideDistribution $originalDistributorQuote, WorldwideDistribution $replicatedDistributorQuote): ReplicatedContactsData
    {
        $newContactModels = [];
        $newContactPivots = [];

        foreach ($originalDistributorQuote->contacts as $contact) {
            $newContact = $contact->replicate();
            $newContact->{$newContact->getKeyName()} = (string)Uuid::generate(4);
            $newContact->{$newContact->getCreatedAtColumn()} = $newContact->freshTimestampString();
            $newContact->{$newContact->getUpdatedAtColumn()} = $newContact->freshTimestampString();

            $newContactModels[] = $newContact;
            $newContactPivots[] = [
                'contact_id' => $newContact->getKey(),
                'replicated_contact_id' => $contact->getKey(),
                'worldwide_distribution_id' => $replicatedDistributorQuote->getKey(),
                'is_default' => $contact->pivot->is_default
            ];
        }

        return new ReplicatedContactsData($newContactModels, $newContactPivots);
    }
}
