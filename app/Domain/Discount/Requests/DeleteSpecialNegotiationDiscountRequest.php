<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteVersion;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteSpecialNegotiationDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        /** @var \App\Domain\Discount\Models\SND $snDiscount */
        $snDiscount = $this->route('snd');

        $rescueQuoteModel = new Quote();
        $rescueQuoteVersionModel = new QuoteVersion();
        $worldwideQuoteModel = new WorldwideQuote();

        $submittedContractWorldwideQuotes = $snDiscount->worldwideContractQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedPackWorldwideQuotes = $snDiscount->worldwidePackQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedRescueQuotes = $snDiscount->rescueQuotes()
            ->whereNotNull($rescueQuoteModel->qualifyColumn('submitted_at'))
            ->whereNull($rescueQuoteModel->activeVersion()->getQualifiedForeignKeyName())
            ->with('customer')
            ->get();

        $submittedRescueQuoteVersions = $snDiscount->rescueQuoteVersions()
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
            return Response::deny(sprintf("You can not delete the special negotiation discount, it's attached to the quotes: %s", $quoteNumbers->implode(', ')));
        }

        return Response::allow();
    }

    public function rules(): array
    {
        return [
        ];
    }
}
