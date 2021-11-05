<?php

namespace App\Http\Requests\Discount;

use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class DeletePromotionalDiscount extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return Response
     */
    public function authorize(): Response
    {
        /** @var PromotionalDiscount $promotionalDiscount */
        $promotionalDiscount = $this->route('promotion');

        $rescueQuoteModel = new Quote();
        $rescueQuoteVersionModel = new QuoteVersion();
        $worldwideQuoteModel = new WorldwideQuote();

        $submittedContractWorldwideQuotes = $promotionalDiscount->worldwideContractQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedPackWorldwideQuotes = $promotionalDiscount->worldwidePackQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedRescueQuotes = $promotionalDiscount->rescueQuotes()
            ->whereNotNull($rescueQuoteModel->qualifyColumn('submitted_at'))
            ->whereNull($rescueQuoteModel->activeVersion()->getQualifiedForeignKeyName())
            ->with('customer')
            ->get();

        $submittedRescueQuoteVersions = $promotionalDiscount->rescueQuoteVersions()
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
            return Response::deny(sprintf("You can not delete the promotional discount, it's attached to the quotes: %s", $quoteNumbers->implode(', ')));
        }

        return Response::allow();
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
}
