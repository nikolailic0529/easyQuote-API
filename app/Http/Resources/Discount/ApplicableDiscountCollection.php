<?php

namespace App\Http\Resources\Discount;

use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class ApplicableDiscountCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $groupedDiscounts = [];

        foreach ($this->resource as $discount) {
            $groupedDiscounts[get_class($discount)][] = $discount;
        }

        return [
            'multi_year' => array_map([$this, 'mapMultiYearDiscount'], $groupedDiscounts[MultiYearDiscount::class] ?? []),
            'pre_pay' => array_map([$this, 'mapPrePayDiscount'], $groupedDiscounts[PrePayDiscount::class] ?? []),
            'promotional' => array_map([$this, 'mapPromotionalDiscount'], $groupedDiscounts[PromotionalDiscount::class] ?? []),
            'snd' => array_map([$this, 'mapSpecialNegotiationDiscount'], $groupedDiscounts[SND::class] ?? []),
        ];
    }

    private function mapMultiYearDiscount(MultiYearDiscount $discount): array
    {
        return [
            'id' => $discount->getKey(),
            'name' => $discount->name,
            'durations' => $discount->durations,
        ];
    }

    private function mapPrePayDiscount(PrePayDiscount $prePayDiscount): array
    {
        return [
            'id' => $prePayDiscount->getKey(),
            'name' => $prePayDiscount->name,
            'durations' => $prePayDiscount->durations,
        ];
    }

    private function mapPromotionalDiscount(PromotionalDiscount $promotionalDiscount): array
    {
        return [
            'id' => $promotionalDiscount->getKey(),
            'name' => $promotionalDiscount->name,
            'value' => $promotionalDiscount->value,
            'minimum_limit' => $promotionalDiscount->minimum_limit,
        ];
    }

    private function mapSpecialNegotiationDiscount(SND $snDiscount): array
    {
        return [
            'id' => $snDiscount->getKey(),
            'name' => $snDiscount->name,
            'value' => $snDiscount->value
        ];
    }
}
