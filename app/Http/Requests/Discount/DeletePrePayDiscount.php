<?php

namespace App\Http\Requests\Discount;

use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class DeletePrePayDiscount extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return Response
     */
    public function authorize(): Response
    {
        /** @var PrePayDiscount $prePayDiscount */
        $prePayDiscount = $this->route('pre_pay');

        $rescueQuoteModel = new Quote();
        $rescueQuoteVersionModel = new QuoteVersion();
        $worldwideQuoteModel = new WorldwideQuote();

        $submittedContractWorldwideQuotes = $prePayDiscount->worldwideContractQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedPackWorldwideQuotes = $prePayDiscount->worldwidePackQuotes()
            ->whereNotNull($worldwideQuoteModel->qualifyColumn('submitted_at'))
            ->get();

        $submittedRescueQuotes = $prePayDiscount->rescueQuotes()
            ->whereNotNull($rescueQuoteModel->qualifyColumn('submitted_at'))
            ->whereNull($rescueQuoteModel->activeVersion()->getQualifiedForeignKeyName())
            ->with('customer')
            ->get();

        $submittedRescueQuoteVersions = $prePayDiscount->rescueQuoteVersions()
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
            return Response::deny(sprintf("You can not delete the pre-pay discount, it's attached to the quotes: %s", $quoteNumbers->implode(', ')));
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
