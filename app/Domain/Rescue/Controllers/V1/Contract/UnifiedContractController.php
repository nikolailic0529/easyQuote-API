<?php

namespace App\Domain\Rescue\Controllers\V1\Contract;

use App\Domain\UnifiedContract\Queries\UnifiedContractQueries;
use App\Domain\UnifiedContract\Requests\EntityLookupRequest;
use App\Domain\UnifiedContract\Resources\V1\ContractCompanyNameCollection;
use App\Domain\UnifiedContract\Resources\V1\ContractNumberCollection;
use App\Domain\UnifiedContract\Resources\V1\CustomerNameCollection;
use App\Domain\UnifiedContract\Resources\V1\UserNameCollection;
use App\Domain\UnifiedContract\Services\ElasticContractLookupService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

class UnifiedContractController extends Controller
{
    /**
     * Show a list of unique user names of drafted contracts.
     */
    public function showUserNamesOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            UserNameCollection::make($queries->uniqueUserNamesOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique user names of submitted contracts.
     */
    public function showUserNamesOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            UserNameCollection::make($queries->uniqueUserNamesOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     */
    public function showCustomerNamesOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            CustomerNameCollection::make($queries->uniqueCustomerNamesOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     */
    public function showCustomerNamesOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            CustomerNameCollection::make($queries->uniqueCustomerNamesOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     */
    public function showNumbersOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            ContractNumberCollection::make($queries->uniqueNumbersOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     */
    public function showNumbersOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            ContractNumberCollection::make($queries->uniqueNumbersOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique company names of contracts.
     */
    public function showCompanyNamesOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            ContractCompanyNameCollection::make($queries->uniqueCompanyNamesOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique company names of contracts.
     */
    public function showCompanyNamesOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            ContractCompanyNameCollection::make($queries->uniqueCompanyNamesOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Search contract customer entities.
     */
    public function searchContractCustomers(EntityLookupRequest $request,
                                            ElasticContractLookupService $lookupService,
                                            string $query = ''): JsonResponse
    {
        $result = $lookupService->performContractCustomersLookup($query);

        return response()->json(
            $request->collapseElasticsearchResult($result)
        );
    }

    /**
     * Search contract company entities.
     */
    public function searchContractCompanies(EntityLookupRequest $request,
                                            ElasticContractLookupService $lookupService,
                                            string $query = ''): JsonResponse
    {
        $result = $lookupService->performContractCompaniesLookup($query);

        return response()->json(
            $request->collapseElasticsearchResult($result)
        );
    }

    /**
     * Search contract numbers.
     */
    public function searchContractNumbers(EntityLookupRequest $request,
                                          ElasticContractLookupService $lookupService,
                                          string $query = ''): JsonResponse
    {
        $result = $lookupService->performContractNumbersLookup($query);

        return response()->json(
            $request->collapseElasticsearchResult($result)
        );
    }
}
