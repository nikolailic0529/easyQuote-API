<?php

namespace App\Domain\Asset\Controllers\V1;

use App\Domain\Asset\DataTransferObjects\ChangeAssetOwnershipData;
use App\Domain\Asset\Models\Asset;
use App\Domain\Shared\Ownership\Services\ModelOwnershipService;
use App\Foundation\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssetOwnershipController extends Controller
{
    /**
     * Change asset ownership.
     */
    public function changeAssetOwnership(
        Request $request,
        ModelOwnershipService $service,
        ChangeAssetOwnershipData $data,
        Asset $asset
    ): Response {
        $this->authorize('changeOwnership', $asset);

        $service->setCauser($request->user())
            ->changeOwnership($asset, $data->toChangeOwnershipData());

        return response()->noContent();
    }
}
