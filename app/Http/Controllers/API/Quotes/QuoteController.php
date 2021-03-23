<?php

namespace App\Http\Controllers\API\Quotes;

use App\Collections\MappedRows;
use App\Contracts\{Repositories\Quote\Margin\MarginRepositoryInterface as MarginRepository,
    Repositories\UserRepositoryInterface as Users,
    Services\QuoteState,
};
use App\Http\Controllers\Controller;
use App\Http\Requests\{GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\StoreQuoteStateRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Http\Requests\Quote\{FirstStep,
    GivePermissionRequest,
    SelectGroupDescriptionRequest,
    SetVersionRequest,
    ShowQuoteState,
    TryDiscountsRequest,
};
use App\Http\Resources\{ImportedRow\MappedRow, QuoteVersionResource, TemplateRepository\TemplateResourceListing,};
use App\Models\Quote\Quote;
use App\Queries\QuoteQueries;
use App\Services\QuoteFileService;
use App\Services\QuotePermissionRegistar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    protected QuoteState $processor;

    protected MarginRepository $margins;

    public function __construct(
        QuoteState $processor,
        MarginRepository $margins
    )
    {
        $this->processor = $processor;
        $this->margins = $margins;
    }

    public function quote(ShowQuoteState $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            QuoteVersionResource::make(
                $request->loadQuoteAttributes($quote)
            )
        );
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        if ($request->has('quote_id')) {
            $this->authorize('state', $request->quote());
        } else {
            $this->authorize('create', Quote::class);
        }

        return response()->json(
            $this->processor->storeState($request)
        );
    }

    public function setVersion(SetVersionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->setVersion($request->version_id, $quote)
        );
    }

    public function step1(FirstStep $request)
    {
        return response()->json($request->data());
    }

    public function step2(MappingReviewRequest $request, QuoteQueries $quoteQueries)
    {
        $this->authorize('view', $quote = $request->getQuote());

        if ($request->has('search')) {
            return response()->json(
                $quoteQueries->searchRowsQuery(
                    $quote->activeVersionOrCurrent,
                    $request->search,
                    $request->group_id
                )->get()
            );
        }

        $rows = MappedRows::make(
            $quoteQueries->mappedOrderedRowsQuery($quote->activeVersionOrCurrent)->get()
        );

        return response()->json(MappedRow::collection($rows));
    }

    /**
     * Show Grouped Rows.
     *
     * @param Quote $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rowsGroups(Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->retrieveRowsGroups($quote->activeVersionOrCurrent)
        );
    }

    public function templates(GetQuoteTemplatesRequest $request): JsonResponse
    {
        return response()->json(
            TemplateResourceListing::collection(
                $request->getTemplatesQuery()->get()
            )
        );
    }

    public function step3()
    {
        return response()->json(
            $this->margins->data()
        );
    }

    /**
     * Get acceptable Discounts for the specified Quote
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function discounts(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->discounts($quote->id)
        );
    }

    /**
     * Try Apply Discounts to the Quote List Price.
     * Return passed discounts with calculated Total Margin after each passed Discount.
     *
     * @param TryDiscountsRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function tryDiscounts(TryDiscountsRequest $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->tryDiscounts($request, $quote->id)
        );
    }

    /**
     * Get Imported Rows Data after Applying Margins
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function review(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->review($quote->id)
        );
    }

    /**
     * Show specified Rows Group Description from specified Quote.
     *
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Request
     */
    public function showGroupDescription(Quote $quote, string $group)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->findGroupDescription($group, $quote)
        );
    }

    /**
     * Store Rows Group Description for specified Quote.
     *
     * @param StoreGroupDescriptionRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function storeGroupDescription(StoreGroupDescriptionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->createGroupDescription($request->validated(), $quote)
        );
    }

    /**
     * Mark as selected specific groups descriptions.
     * Non-passed groups ids will be marked as unselected.
     *
     * @param SelectGroupDescriptionRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function selectGroupDescription(SelectGroupDescriptionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->selectGroupDescription($request->validated(), $quote)
        );
    }

    /**
     * Store Rows Group Description for specified Quote.
     *
     * @param UpdateGroupDescriptionRequest $request
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Response
     */
    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, Quote $quote, string $group)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->updateGroupDescription($group, $quote, $request->validated())
        );
    }

    /**
     * Move specified Rows to specified Rows Group Description for specified Quote.
     *
     * @param MoveGroupDescriptionRowsRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->moveGroupDescriptionRows($quote, $request->validated())
        );
    }

    /**
     * Remove specified Rows Group Description from specified Quote.
     *
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Response
     */
    public function destroyGroupDescription(Quote $quote, string $group)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->deleteGroupDescription($group, $quote)
        );
    }

    /**
     * Download specific existing Quote file.
     *
     * @param Quote $quote
     * @param string $fileType
     * @return \Illuminate\Http\Response
     */
    public function downloadQuoteFile(Quote $quote, string $fileType, QuoteFileService $service)
    {
        $this->authorize('downloadFile', [$quote, $fileType]);

        return $service->downloadQuoteFile($quote, $fileType);
    }

    /**
     * Display a listing of the quote authorized users.
     *
     * @param Quote $quote
     * @param Users $users User repository
     * @return \Illuminate\Http\Response
     */
    public function showAuthorizedQuoteUsers(Quote $quote, Users $users)
    {
        $this->authorize('grantPermission', $quote);

        $permission = $this->processor->getQuotePermission($quote, ['read', 'update']);

        return response()->json(
            $users->getUsersWithPermission($permission)
        );
    }

    /**
     * Give read/update permission to specific quote resource.
     *
     * @param GivePermissionRequest $request
     * @param Quote $quote
     * @param Users $users User repository
     * @return \Illuminate\Http\Response
     */
    public function givePermissionToQuote(GivePermissionRequest $request, QuotePermissionRegistar $permissionRegistar, Quote $quote, Users $users)
    {
        $this->authorize('grantPermission', $quote);

        $permission = $this->processor->getQuotePermission($quote, ['read', 'update']);

        $authorized = $users->syncUsersPermission($request->users, $permission);

        $permissionRegistar->handleQuoteGrantedUsers($quote, $authorized);

        return response()->json(true);
    }
}
