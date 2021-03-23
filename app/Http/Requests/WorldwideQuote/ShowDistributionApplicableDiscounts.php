<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\VendorsAndCountryData;
use App\Models\Quote\WorldwideDistribution;
use App\Queries\DiscountQueries;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;

class ShowDistributionApplicableDiscounts extends FormRequest
{
    protected DiscountQueries $queries;

    public function __construct(DiscountQueries $queries)
    {
        $this->queries = $queries;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function getApplicableDiscounts(): Collection
    {
        /** @var WorldwideDistribution $distribution */
        $distribution = $this->route('worldwide_distribution');

        $query = $this->queries->discountsForVendorsAndCountryQuery(
            new VendorsAndCountryData([
                'vendor_keys' => $distribution->vendors()->pluck('id')->all(),
                'country_id' => $distribution->country_id,
            ])
        );

        $discounts = [];

        foreach ($query->get()->groupBy('discount_type') as $discountClass => $modelKeys) {
            if (! class_exists($discountClass)) {
                throw new \Error("Class $discountClass does not exist");
            }

            $discounts[] = $discountClass::whereKey(Arr::pluck($modelKeys, 'id'))->get();
        }

        return new Collection(Arr::collapse($discounts));
    }
}
