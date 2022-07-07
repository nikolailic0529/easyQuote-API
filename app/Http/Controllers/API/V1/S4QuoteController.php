<?php

namespace App\Http\Controllers\API\V1;

use App\Contracts\Repositories\{Quote\QuoteSubmittedRepositoryInterface as QuoteSubmittedRepository,};
use App\Contracts\Services\CustomerState;
use App\Contracts\Services\QuoteView;
use App\Http\Controllers\Controller;
use App\Http\Middleware\HttpResponseLogger;
use App\Http\Requests\S4\StoreContractRequest;
use App\Http\Resources\V1\CustomerResponseResource;
use App\Http\Resources\V1\QuoteResource;
use App\Models\Customer\Customer;
use App\Models\Quote\Quote;
use App\Queries\QuoteQueries;
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
     *
     * @param Request $request
     * @param QuoteView $quoteViewService
     * @param string $rfq
     * @return JsonResponse
     */
    public function showQuoteByRfqNumber(Request   $request,
                                         QuoteView $quoteViewService,
                                         string    $rfq): JsonResponse
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
     *
     * @param string $rfq
     * @return BinaryFileResponse
     */
    public function downloadPriceListFile(string $rfq): BinaryFileResponse
    {
        return response()->download(
            $this->quote->price($rfq)
        );
    }

    /**
     * Download an existing payment schedule file by RFQ number.
     *
     * @param string $rfq
     * @return BinaryFileResponse
     */
    public function downloadPaymentScheduleFile(string $rfq): BinaryFileResponse
    {
        return response()->download(
            $this->quote->schedule($rfq)
        );
    }

    /**
     * Retrieve if exists Generated PDF file by RFQ number.
     *
     * @param Request $request
     * @param QuoteQueries $queries
     * @param QuoteView $quoteViewService
     * @param string $rfq
     * @return Response
     */
    public function exportToPdf(Request      $request,
                                QuoteQueries $queries,
                                QuoteView    $quoteViewService,
                                string       $rfq): Response
    {
        /** @var Quote $quote */
        $quote = $queries->quoteByRfqNumberQuery($rfq)->sole();

        return $quoteViewService
            ->setCauser($request->user())
            ->export($quote);
    }

    /**
     * Receive Quote Data from S4 and Create a Customer with New RFQ number.
     *
     * @param StoreContractRequest $request
     * @param CustomerState $processor
     * @return JsonResponse
     */
    public function storeS4Customer(StoreContractRequest $request,
                                    CustomerState        $processor): JsonResponse
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
