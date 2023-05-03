<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteVersion;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteMultiYearDiscountRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var MultiYearDiscount $multiYearDiscount */
        $multiYearDiscount = $this->route('multi_year');

        $rescueQuoteModel = new Quote();
        $rescueQuoteVersionModel = new QuoteVersion();
        $worldwideQuoteModel = new WorldwideQuote();

        $submittedContractWorldwideQuotes = $multiYearDiscount->worldwideContractQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedPackWorldwideQuotes = $multiYearDiscount->worldwidePackQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedRescueQuotes = $multiYearDiscount->rescueQuotes()
            ->whereNotNull($rescueQuoteModel->qualifyColumn('submitted_at'))
            ->whereNull($rescueQuoteModel->activeVersion()->getQualifiedForeignKeyName())
            ->with('customer')
            ->get();

        $submittedRescueQuoteVersions = $multiYearDiscount->rescueQuoteVersions()
            ->join($rescueQuoteModel->getTable(), $rescueQuoteModel->activeVersion()->getQualifiedForeignKeyName(), $rescueQuoteVersionModel->quote()->getQualifiedForeignKeyName())
            ->select($rescueQuoteVersionModel->qualifyColumn('*'))
            ->with('quote.customer')
            ->get();

        $worldwideQuoteNumbers = collect()
            ->merge($submittedContractWorldwideQuotes->pluck('quote_number'))
            ->merge($submittedPackWorldwideQuotes->pluck('quote_number'));

        $rescueQuoteNumbers = collect()
            ->merge($submittedRescueQuotes->pluck('customer.rfq'))
            ->merge($submittedRescueQuoteVersions->pluck('quote.customer.rfq'));

        $quoteNumbers = $worldwideQuoteNumbers->merge($rescueQuoteNumbers);

        if ($quoteNumbers->isNotEmpty()) {
            return Response::deny(sprintf("You can not delete the multi-year discount, it's attached to the quotes: %s", $quoteNumbers->implode(', ')));
        }

        return Response::allow();
    }

    public function rules(): array
    {
        return [
        ];
    }
}
