<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class ShowPackQuoteApplicableDiscountsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function getApplicableDiscounts(WorldwideQuote $quote): Collection
    {
        $version = $quote->activeVersion;

        $vendorIDsFromSelectedAssets = value(function () use ($version): array {
            $assetModel = new WorldwideQuoteAsset();
            $assetGroupModel = new WorldwideQuoteAssetsGroup();

            if (!$version->use_groups) {
                return $version->assets()->getQuery()
                    ->where($assetModel->qualifyColumn('is_selected'), true)
                    ->distinct($assetModel->qualifyColumn('vendor_id'))
                    ->pluck($assetModel->qualifyColumn('vendor_id'))
                    ->all();
            }

            return $version->assetsGroups()->getQuery()
                ->where($assetGroupModel->qualifyColumn('is_selected'), true)
                ->join($assetGroupModel->assets()->getTable(), $assetGroupModel->assets()->getQualifiedForeignPivotKeyName(), $assetGroupModel->getQualifiedKeyName())
                ->join($assetModel->getTable(), $assetModel->getQualifiedKeyName(), $assetGroupModel->assets()->getQualifiedRelatedPivotKeyName())
                ->select($assetModel->qualifyColumn('vendor_id'))
                ->distinct($assetModel->qualifyColumn('vendor_id'))
                ->pluck($assetModel->qualifyColumn('vendor_id'))
                ->all();
        });

        $discounts = collect([
            MultiYearDiscount::query()->whereIn('vendor_id', $vendorIDsFromSelectedAssets)->get(),
            PrePayDiscount::query()->whereIn('vendor_id', $vendorIDsFromSelectedAssets)->get(),
            PromotionalDiscount::query()->whereIn('vendor_id', $vendorIDsFromSelectedAssets)->get(),
            SND::query()->whereIn('vendor_id', $vendorIDsFromSelectedAssets)->get(),
        ])
            ->collapse()
            ->all();

        return new Collection($discounts);
    }
}
