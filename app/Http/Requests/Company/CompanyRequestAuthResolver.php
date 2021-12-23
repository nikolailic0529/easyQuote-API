<?php

namespace App\Http\Requests\Company;

use App\Models\Address;
use App\Models\QuoteFile\MappedRow;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

final class CompanyRequestAuthResolver
{
    public function __invoke(FormRequest $request): Response
    {
        $addressModelKeys = $request->input('addresses.*.id');

        $detachedAddressModelKeys = collect($request->company->addresses()->get()->modelKeys())
            ->diff($addressModelKeys);

        $addressModel = new Address();
        $packAssetModel = new WorldwideQuoteAsset();
        $contractAssetModel = new MappedRow();

        $packAssetsWhereDetachedAddressUsed = $packAssetModel->newQuery()
            ->join($addressModel->getTable(), $addressModel->getQualifiedKeyName(), $packAssetModel->machineAddress()->getQualifiedForeignKeyName())
            ->whereIn($addressModel->getQualifiedKeyName(), $detachedAddressModelKeys->all())
            ->get()
            ->toBase();

        $contractAssetsWhereDetachedAddressUsed = $contractAssetModel->newQuery()
            ->join($addressModel->getTable(), $addressModel->getQualifiedKeyName(), $contractAssetModel->machineAddress()->getQualifiedForeignKeyName())
            ->whereIn($addressModel->getQualifiedKeyName(), $detachedAddressModelKeys->all())
            ->get()
            ->toBase();

        $quoteNumbers = $packAssetsWhereDetachedAddressUsed->map(function (WorldwideQuoteAsset $asset) {
            return $asset->worldwideQuoteVersion?->worldwideQuote?->quote_number;
        })
            ->values();

        $quoteNumbers->merge($contractAssetsWhereDetachedAddressUsed->map(function (MappedRow $row) {
            return $row->worldwideQuoteVersion?->worldwideQuote?->quote_number;
        }));

        $quoteNumbers = $quoteNumbers
            ->reject(fn(?string $number) => is_null($number))
            ->unique()
            ->values();

        if ($quoteNumbers->isNotEmpty()) {
            return Response::deny(sprintf("You cannot detach some of the addresses, used in the quotes: %s.", $quoteNumbers->implode(', ')));
        }

        return Response::allow();
    }
}