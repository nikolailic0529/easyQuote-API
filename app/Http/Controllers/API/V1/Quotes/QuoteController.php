<?php

namespace App\Http\Controllers\API\V1\Quotes;

use App\Collections\MappedRows;
use App\Contracts\{Repositories\Quote\Margin\MarginRepositoryInterface as MarginRepository,
    Repositories\UserRepositoryInterface as Users,
    Services\QuoteState,};
use App\Http\Controllers\Controller;
use App\Http\Requests\{GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\StoreQuoteStateRequest,
    Quote\UpdateGroupDescriptionRequest};
use App\Http\Requests\Quote\{FirstStep,
    GivePermissionRequest,
    SelectGroupDescriptionRequest,
    SetVersionRequest,
    ShowQuoteState,
    TryDiscountsRequest,};
use App\Http\Resources\{V1\Appointment\AppointmentListResource,
    V1\ImportedRow\MappedRow,
    V1\QuoteVersionResource,
    V1\TemplateRepository\TemplateResourceListing};
use App\Models\Quote\Quote;
use App\Queries\AppointmentQueries;
use App\Queries\QuoteQueries;
use App\Services\QuoteFileService;
use App\Services\QuotePermissionRegistar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuoteController extends Controller
{
    public function __construct(protected QuoteState       $processor,
                                protected MarginRepository $margins)
    {
    }

    /**
     * Show state of the quote entity.
     *
     * @param ShowQuoteState $request
     * @param Quote $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function quote(ShowQuoteState $request, Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        return response()->json(
            QuoteVersionResource::make(
                $request->loadQuoteAttributes($quote)
            )
        );
    }

    /**
     * Update state of the quote entity.
     *
     * @param StoreQuoteStateRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeState(StoreQuoteStateRequest $request): JsonResponse
    {
        if ($request->has('quote_id')) {
            $this->authorize('state', $request->getQuote());
        } else {
            $this->authorize('create', Quote::class);
        }

        return response()->json(
            $this->processor->storeState($request)
        );
    }

    /**
     * Set the specified version as active for the quote entity.
     *
     * @param SetVersionRequest $request
     * @param Quote $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function setVersion(SetVersionRequest $request, Quote $quote): JsonResponse
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->setVersion($request->version_id, $quote)
        );
    }

    /**
     * Show form data for the 1st step.
     *
     * @param FirstStep $request
     * @return JsonResponse
     */
    public function step1(FirstStep $request): JsonResponse
    {
        return response()->json($request->data());
    }

    /**
     * Show form data for the 2nd step.
     *
     * @param MappingReviewRequest $request
     * @param QuoteQueries $quoteQueries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function step2(MappingReviewRequest $request, QuoteQueries $quoteQueries): JsonResponse
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

    /**
     * Show a listing of the filtered templates.
     *
     * @param GetQuoteTemplatesRequest $request
     * @return JsonResponse
     */
    public function templates(GetQuoteTemplatesRequest $request): JsonResponse
    {
        return response()->json(
            TemplateResourceListing::collection(
                $request->getTemplatesQuery()->get()
            )
        );
    }

    /**
     * Show form data for the 3rd step.
     *
     * @return JsonResponse
     */
    public function step3(): JsonResponse
    {
        return response()->json(
            $this->margins->data()
        );
    }

    /**
     * Get acceptable Discounts for the specified Quote
     *
     * @param Quote $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function discounts(Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->discounts($quote->id)
        );
    }

    /**
     * Try to apply the given discounts to the Quote List Price.
     * Return passed discounts with calculated Total Margin after each passed Discount.
     *
     * @param TryDiscountsRequest $request
     * @param Quote $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function tryDiscounts(TryDiscountsRequest $request, Quote $quote): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function review(Quote $quote): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showGroupDescription(Quote $quote, string $group): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeGroupDescription(StoreGroupDescriptionRequest $request, Quote $quote): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function selectGroupDescription(SelectGroupDescriptionRequest $request, Quote $quote): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateGroupDescription(UpdateGroupDescriptionRequest $request,
                                           Quote                         $quote,
                                           string                        $group): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, Quote $quote): JsonResponse
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyGroupDescription(Quote $quote, string $group): JsonResponse
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
     * @return \App\Http\Resources\V1\DownloadableQuoteFile
     * @throws AuthorizationException
     */
    public function downloadQuoteFile(Quote            $quote,
                                      QuoteFileService $service,
                                      string           $fileType): \App\Http\Resources\V1\DownloadableQuoteFile
    {
        $this->authorize('downloadFile', [$quote, $fileType]);

        return $service->downloadQuoteFile($quote, $fileType);
    }

    /**
     * Display a listing of the quote authorized users.
     *
     * @param Quote $quote
     * @param Users $users User repository
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showAuthorizedQuoteUsers(Quote $quote, Users $users): JsonResponse
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
     * @param QuotePermissionRegistar $permissionRegistar
     * @param Quote $quote
     * @param Users $users User repository
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function givePermissionToQuote(GivePermissionRequest   $request,
                                          QuotePermissionRegistar $permissionRegistar,
                                          Quote                   $quote,
                                          Users                   $users): JsonResponse
    {
        $this->authorize('grantPermission', $quote);

        $permission = $this->processor->getQuotePermission($quote, ['read', 'update']);

        $authorized = $users->syncUsersPermission($request->users, $permission);

        $permissionRegistar->handleQuoteGrantedUsers($quote, $authorized);

        return response()->json(true);
    }


    /**
     * List appointments linked to quote.
     *
     * @param Request $request
     * @param AppointmentQueries $appointmentQueries
     * @param Quote $quote
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAppointmentsOfQuote(Request            $request,
                                            AppointmentQueries $appointmentQueries,
                                            Quote              $quote): AnonymousResourceCollection
    {
        $this->authorize('view', $quote);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($quote, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
