<?php

namespace App\Domain\Rescue\Controllers\V1\Customer;

use App\Domain\Rescue\Contracts\CustomerState;
use App\Domain\Rescue\Contracts\QuoteSubmittedRepositoryInterface as QuoteSubmittedRepository;
use App\Domain\Rescue\Contracts\QuoteView;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Rescue\Requests\StoreContractRequest;
use App\Domain\Rescue\Resources\V1\CustomerResponseResource;
use App\Domain\Rescue\Resources\V1\QuoteResource;
use App\Foundation\Http\Controller;
use App\Foundation\Http\Middleware\HttpResponseLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class S4QuoteController extends Controller
{
    protected $quote;

    public function __construct(QuoteSubmittedRepository $quote)
    {
        $this->quote = $quote;

        $this->middleware(['client', HttpResponseLogger::class]);
    }

    /**
     * Handle the incoming request.
     */
    public function showQuoteByRfqNumber(Request $request,
                                         QuoteView $quoteViewService,
                                         string $rfq): JsonResponse
    {
        $resource = $quoteViewService
            ->setCauser($request->user())
            ->requestForQuote(rfqNumber: $rfq);

        return response()->json(
            QuoteResource::make($resource)
        );
    }

    /**
     * Download an existing price list file by RFQ number.
     */
    public function downloadPriceListFile(string $rfq): BinaryFileResponse
    {
        return response()->download(
            $this->quote->price($rfq)
        );
    }

    /**
     * Download an existing payment schedule file by RFQ number.
     */
    public function downloadPaymentScheduleFile(string $rfq): BinaryFileResponse
    {
        return response()->download(
            $this->quote->schedule($rfq)
        );
    }

    /**
     * Retrieve if exists Generated PDF file by RFQ number.
     */
    public function exportToPdf(Request $request,
                                QuoteQueries $queries,
                                QuoteView $quoteViewService,
                                string $rfq): Response
    {
        /** @var \App\Domain\Rescue\Models\Quote $quote */
        $quote = $queries->quoteByRfqNumberQuery($rfq)->sole();

        return $quoteViewService
            ->setCauser($request->user())
            ->export($quote);
    }

    /**
     * Receive Quote Data from S4 and Create a Customer with New RFQ number.
     */
    public function storeS4Customer(StoreContractRequest $request,
                                    CustomerState $processor): JsonResponse
    {
        $resource = tap($processor->createFromS4Data($request->getS4CustomerData()), function (Customer $customer) use ($request) {
            $customer->load('country', 'addresses');
            $request->dispatchReceivedEvent($customer);
        });

        return response()->json(
            CustomerResponseResource::make($resource)
        );
    }
}
