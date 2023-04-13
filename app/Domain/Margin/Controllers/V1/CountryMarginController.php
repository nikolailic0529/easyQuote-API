<?php

namespace App\Domain\Margin\Controllers\V1;

use App\Domain\Margin\Contracts\MarginRepositoryInterface as MarginRepository;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Margin\Requests\StoreCountryMarginRequest;
use App\Domain\Margin\Requests\UpdateCountryMarginRequest;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class CountryMarginController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CountryMargin::class, 'margin');
    }

    /**
     * Paginate margins.
     */
    public function index(MarginRepository $repository): JsonResponse
    {
        return response()->json(
            request()->filled('search')
                ? $repository->search(request('search'))
                : $repository->all()
        );
    }

    /**
     * Show margin.
     */
    public function show(CountryMargin $margin): JsonResponse
    {
        return response()->json($margin);
    }

    /**
     * Create margin.
     */
    public function store(StoreCountryMarginRequest $request, MarginRepository $repository): JsonResponse
    {
        return response()->json(
            $repository->create($request)
        );
    }

    /**
     * Update margin.
     */
    public function update(
        UpdateCountryMarginRequest $request,
        MarginRepository $repository,
        CountryMargin $margin
    ): JsonResponse {
        return response()->json(
            $repository->update($request, $margin->getKey())
        );
    }

    /**
     * Delete margin.
     */
    public function destroy(MarginRepository $repository, CountryMargin $margin): JsonResponse
    {
        return response()->json(
            $repository->delete($margin->getKey())
        );
    }

    /**
     * Mark margin as active.
     *
     * @throws AuthorizationException
     */
    public function activate(MarginRepository $repository, CountryMargin $margin): JsonResponse
    {
        $this->authorize('update', $margin);

        return response()->json(
            $repository->activate($margin->getKey())
        );
    }

    /**
     * Mark margin as inactive.
     *
     * @throws AuthorizationException
     */
    public function deactivate(MarginRepository $repository, CountryMargin $margin): JsonResponse
    {
        $this->authorize('update', $margin);

        return response()->json(
            $repository->deactivate($margin->getKey())
        );
    }
}
