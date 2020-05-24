<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\{
    Services\QuoteState,
    Repositories\Quote\Margin\MarginRepositoryInterface as MarginRepository,
    Repositories\CompanyRepositoryInterface as CompanyRepository,
    Repositories\CurrencyRepositoryInterface as CurrencyRepository,
    Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository,
    Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectRepository,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Repositories\UserRepositoryInterface as Users,
    Repositories\RoleRepositoryInterface as Roles,
};
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Http\Requests\{
    Quote\StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Http\Requests\Quote\{
    GiveModulePermission,
    GivePermissionRequest,
    SelectGroupDescriptionRequest,
    SetVersionRequest,
    TryDiscountsRequest,
};
use App\Http\Resources\{
    QuoteVersionResource,
    ImportedRow\MappedRow,
    TemplateRepository\TemplateResourceListing,
};
use App\Models\Quote\Quote;
use Setting;

class QuoteController extends Controller
{
    protected QuoteState $processor;

    protected QuoteTemplateRepository $quoteTemplates;

    protected QuoteFileRepository $quoteFiles;

    protected MarginRepository $margins;

    protected CompanyRepository $companies;

    protected CurrencyRepository $currencies;

    protected DataSelectRepository $dataSelects;

    public function __construct(
        QuoteState $processor,
        QuoteTemplateRepository $quoteTemplates,
        QuoteFileRepository $quoteFiles,
        MarginRepository $margins,
        CompanyRepository $companies,
        DataSelectRepository $dataSelects,
        CurrencyRepository $currencies
    ) {
        $this->processor = $processor;
        $this->quoteTemplates = $quoteTemplates;
        $this->quoteFiles = $quoteFiles;
        $this->margins = $margins;
        $this->companies = $companies;
        $this->dataSelects = $dataSelects;
        $this->currencies = $currencies;
    }

    public function quote(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            filter(QuoteVersionResource::make($quote))
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

    public function step1()
    {
        return response()->json(
            [
                'companies'                 => $this->companies->allInternalWithVendorsAndCountries(),
                'data_select_separators'    => $this->dataSelects->all(),
                'supported_file_types'      => Setting::get('supported_file_types_ui'),
                'currencies'                => $this->currencies->allHaveExrate()
            ]
        );
    }

    public function step2(MappingReviewRequest $request)
    {
        $this->authorize('view', $quote = $request->getQuote());

        if ($request->has('search')) {
            return response()->json(
                $this->processor->searchRows($quote->usingVersion, $request->search, $request->group_id)
            );
        }

        $rows = cache()->sear(
            $quote->usingVersion->mappingReviewCacheKey,
            fn () => $this->processor->retrieveRows($quote->usingVersion)
        );

        return response()->json(MappedRow::collection($rows));
    }

    /**
     * Show Grouped Rows.
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function rowsGroups(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->processor->retrieveRowsGroups($quote->usingVersion)
        );
    }

    public function templates(GetQuoteTemplatesRequest $request)
    {
        $resource = $request->repository()->findByCompanyVendorCountry($request);

        return response()->json(TemplateResourceListing::collection($resource));
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
            $this->processor->findGroupDescription($group, $quote->id)
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
            $this->processor->createGroupDescription($request, $quote->id)
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
            $this->processor->selectGroupDescription($request->validated(), $quote->id)
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
            $this->processor->updateGroupDescription($request, $group, $quote->id)
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
            $this->processor->moveGroupDescriptionRows($request, $quote->id)
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
            $this->processor->deleteGroupDescription($group, $quote->id)
        );
    }

    /**
     * Download specific existing Quote file.
     *
     * @param Quote $quote
     * @param string $fileType
     * @return \Illuminate\Http\Response
     */
    public function downloadQuoteFile(Quote $quote, string $fileType)
    {
        $this->authorize('downloadFile', [$quote, $fileType]);

        $quoteFile = $this->quoteFiles->findByClause([
            'quote_id' => $quote->usingVersion->id,
            'file_type' => $this->quoteFiles->resolveFileType($fileType)
        ]);

        $filepath = $this->quoteFiles->resolveFilepath($quoteFile);

        return response()->download($filepath, $quoteFile->original_file_name);
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
     * Give module permissions to specific roles.
     *
     * @param  GiveModulePermission $request
     * @return \Illuminate\Http\Response
     */
    public function giveModulePermission(GiveModulePermission $request, Roles $roles)
    {
        // 
    }

    /**
     * Give read/update permission to specific quote resource.
     *
     * @param GivePermissionRequest $request
     * @param Quote $quote
     * @param Users $users User repository
     * @return \Illuminate\Http\Response
     */
    public function givePermissionToQuote(GivePermissionRequest $request, QuoteService $service, Quote $quote, Users $users)
    {
        $this->authorize('grantPermission', $quote);

        $permission = $this->processor->getQuotePermission($quote, ['read', 'update']);

        $authorized = $users->syncUsersPermission($request->users, $permission);

        $service->handleQuoteGrantedUsers($quote, $authorized);

        return response()->json(true);
    }
}
