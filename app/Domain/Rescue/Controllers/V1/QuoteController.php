<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\Appointment\Queries\AppointmentQueries;
use App\Domain\Appointment\Resources\V1\AppointmentListResource;
use App\Domain\DocumentMapping\Collections\MappedRows;
use App\Domain\DocumentMapping\Resources\V1\MappedRow;
use App\Domain\Margin\Contracts\MarginRepositoryInterface as MarginRepository;
use App\Domain\QuoteFile\Services\QuoteFileService;
use App\Domain\Rescue\Contracts\QuoteState;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Rescue\Requests\{MappingReviewRequest, ShowThirdStepDataRequest};
use App\Domain\Rescue\Requests\FirstStepRequest;
use App\Domain\Rescue\Requests\GivePermissionRequest;
use App\Domain\Rescue\Requests\MoveGroupDescriptionRowsRequest;
use App\Domain\Rescue\Requests\SelectGroupDescriptionRequest;
use App\Domain\Rescue\Requests\SetVersionRequest;
use App\Domain\Rescue\Requests\ShowQuoteStateRequest;
use App\Domain\Rescue\Requests\StoreGroupDescriptionRequest;
use App\Domain\Rescue\Requests\StoreQuoteStateRequest;
use App\Domain\Rescue\Requests\TryDiscountsRequest;
use App\Domain\Rescue\Requests\UpdateGroupDescriptionRequest;
use App\Domain\Rescue\Services\QuotePermissionRegistrar;
use App\Domain\Template\Requests\QuoteTemplate\GetQuoteTemplatesRequest;
use App\Domain\Template\Resources\V1\TemplateResourceListing;
use App\Domain\User\Contracts\UserRepositoryInterface as Users;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuoteController extends Controller
{
    public function __construct(protected QuoteState $processor,
                                protected MarginRepository $margins)
    {
    }

    /**
     * Show state of the quote entity.
     *
     * @throws AuthorizationException
     */
    public function quote(ShowQuoteStateRequest $request, Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        return response()->json(
            \App\Domain\Rescue\Resources\V1\QuoteVersionResource::make(
                $request->loadQuoteAttributes($quote)
            )
        );
    }

    /**
     * Update state of the quote entity.
     *
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
     */
    public function step1(FirstStepRequest $request): JsonResponse
    {
        return response()->json($request->data());
    }

    /**
     * Show form data for the 2nd step.
     *
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
     */
    public function step3(ShowThirdStepDataRequest $request): JsonResponse
    {
        return response()->json(
            $request->getData()
        );
    }

    /**
     * Get acceptable Discounts for the specified Quote.
     *
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
     * Get Imported Rows Data after Applying Margins.
     *
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
     * @throws AuthorizationException
     */
    public function updateGroupDescription(UpdateGroupDescriptionRequest $request,
                                           Quote $quote,
                                           string $group): JsonResponse
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->processor->updateGroupDescription($group, $quote, $request->validated())
        );
    }

    /**
     * Move specified Rows to specified Rows Group Description for specified Quote.
     *
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
     * @throws AuthorizationException
     */
    public function downloadQuoteFile(Quote $quote,
                                      QuoteFileService $service,
                                      string $fileType): \App\Domain\QuoteFile\Resources\V1\DownloadableQuoteFile
    {
        $this->authorize('downloadFile', [$quote, $fileType]);

        return $service->downloadQuoteFile($quote, $fileType);
    }

    /**
     * Display a listing of the quote authorized users.
     *
     * @param Users $users User repository
     *
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
     * @param Users $users User repository
     *
     * @throws AuthorizationException
     */
    public function givePermissionToQuote(
        GivePermissionRequest $request,
        QuotePermissionRegistrar $permissionRegistar,
        Quote $quote,
        Users $users): JsonResponse
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
     * @throws AuthorizationException
     */
    public function showAppointmentsOfQuote(Request $request,
                                            AppointmentQueries $appointmentQueries,
                                            Quote $quote): AnonymousResourceCollection
    {
        $this->authorize('view', $quote);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($quote, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
