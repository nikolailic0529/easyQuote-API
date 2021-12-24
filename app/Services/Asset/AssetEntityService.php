<?php

namespace App\Services\Asset;

use App\DTO\Asset\CreateAssetData;
use App\DTO\Asset\UpdateAssetData;
use App\Events\Asset\AssetCreated;
use App\Events\Asset\AssetDeleted;
use App\Events\Asset\AssetUpdated;
use App\Models\Asset;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AssetEntityService
{
    protected ConnectionInterface $connection;

    protected ValidatorInterface $validator;

    protected EventDispatcher $eventDispatcher;

    public function __construct(ConnectionInterface $connection,
                                ValidatorInterface $validator,
                                EventDispatcher $eventDispatcher)
    {
        $this->connection = $connection;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createAsset(CreateAssetData $data): Asset
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap(new Asset(), function (Asset $asset) use ($data) {
            $asset->assetCategory()->associate($data->asset_category_id);
            $asset->vendor()->associate($data->vendor_id);
            $asset->address()->associate($data->address_id);
            $asset->vendor_short_code = $data->vendor_short_code;
            $asset->unit_price = $data->unit_price;
            $asset->base_warranty_start_date = $data->base_warranty_start_date;
            $asset->base_warranty_end_date = $data->base_warranty_end_date;
            $asset->active_warranty_start_date = $data->active_warranty_start_date;
            $asset->active_warranty_end_date = $data->active_warranty_end_date;
            $asset->item_number = $data->item_number;
            $asset->product_number = $data->product_number;
            $asset->serial_number = $data->serial_number;
            $asset->product_description = $data->product_description;
            $asset->product_image = $data->product_image;

            $this->connection->transaction(fn() => $asset->save());

            $this->eventDispatcher->dispatch(
                new AssetCreated($asset)
            );
        });
    }

    public function updateAsset(Asset $asset, UpdateAssetData $data): Asset
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap($asset, function (Asset $asset) use ($data) {
            $asset->assetCategory()->associate($data->asset_category_id);
            $asset->vendor()->associate($data->vendor_id);
            $asset->address()->associate($data->address_id);
            $asset->vendor_short_code = $data->vendor_short_code;
            $asset->unit_price = $data->unit_price;
            $asset->base_warranty_start_date = $data->base_warranty_start_date;
            $asset->base_warranty_end_date = $data->base_warranty_end_date;
            $asset->active_warranty_start_date = $data->active_warranty_start_date;
            $asset->active_warranty_end_date = $data->active_warranty_end_date;
            $asset->item_number = $data->item_number;
            $asset->product_number = $data->product_number;
            $asset->serial_number = $data->serial_number;
            $asset->product_description = $data->product_description;
            $asset->product_image = $data->product_image;

            $this->connection->transaction(fn() => $asset->save());

            $this->eventDispatcher->dispatch(
                new AssetUpdated($asset)
            );
        });
    }

    public function deleteAsset(Asset $asset): void
    {
        $this->connection->transaction(function () use ($asset) {
            $asset->delete();
        });

        $this->eventDispatcher->dispatch(
            new AssetDeleted($asset)
        );
    }
}
