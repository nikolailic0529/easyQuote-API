<?php

namespace App\Domain\Worldwide\Services\SalesOrder;

use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;

class SalesOrderAssetsCountResolver
{
    public function __construct(protected readonly SalesOrder $order)
    {
    }

    public static function of(SalesOrder $order): static
    {
        return new static($order);
    }

    public function __invoke(): int
    {
        $contractType = $this->order->worldwideQuote->opportunity->contractType;

        return match ($contractType->getKey()) {
            CT_PACK => $this->countAssetsOfPackOrder(),
            CT_CONTRACT => $this->countAssetsOfContractOrder(),
        };
    }

    private function countAssetsOfPackOrder(): int
    {
        if ($this->order->worldwideQuote->activeVersion->use_groups) {
            return $this->order
                ->worldwideQuote
                ->activeVersion
                ->assetsGroups()
                ->where('is_selected', true)
                ->get()
                ->sum(static function (WorldwideQuoteAssetsGroup $group): int {
                    return $group->assets()->count();
                });
        }

        return $this->order
            ->worldwideQuote
            ->assets()
            ->where('is_selected', true)
            ->count();
    }

    private function countAssetsOfContractOrder(): int
    {
        return $this->order
            ->worldwideQuote
            ->activeVersion
            ->worldwideDistributions
            ->sum(static function (WorldwideDistribution $distribution): int {
                if ($distribution->use_groups) {
                    return $distribution
                        ->rowsGroups()
                        ->where('is_selected', true)
                        ->get()
                        ->sum(static function (DistributionRowsGroup $group): int {
                            return $group->rows()->count();
                        });
                }

                return $distribution
                    ->mappedRows()
                    ->where('is_selected', true)
                    ->count();
            });
    }
}
