<?php

namespace App\Http\Controllers\API\Contracts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Elasticsearch\EntityLookup;
use App\Http\Resources\UnifiedContract\ContractCompanyNameCollection;
use App\Http\Resources\UnifiedContract\ContractNumberCollection;
use App\Http\Resources\UnifiedContract\CustomerNameCollection;
use App\Http\Resources\UnifiedContract\UserNameCollection;
use App\Queries\UnifiedContractQueries;
use App\Services\Contract\ElasticContractLookupService;
use Illuminate\Http\JsonResponse;

class UnifiedContractController extends Controller
{
    /**
     * Show a list of unique user names of drafted contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showUserNamesOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            UserNameCollection::make($queries->uniqueUserNamesOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique user names of submitted contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showUserNamesOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            UserNameCollection::make($queries->uniqueUserNamesOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCustomerNamesOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            CustomerNameCollection::make($queries->uniqueCustomerNamesOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCustomerNamesOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            CustomerNameCollection::make($queries->uniqueCustomerNamesOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showNumbersOfDraftedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            ContractNumberCollection::make($queries->uniqueNumbersOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique customer names of contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showNumbersOfSubmittedContracts(UnifiedContractQueries $queries): JsonResponse
    {
        return response()->json(
            ContractNumberCollection::make($queries->uniqueNumbersOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique company names of contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCompanyNamesOfDraftedContracts(UnifiedContractQueries $queries):JsonResponse
    {
        return response()->json(
            ContractCompanyNameCollection::make($queries->uniqueCompanyNamesOfDraftedContractsQuery()->get())
        );
    }

    /**
     * Show a list of unique company names of contracts.
     *
     * @param \App\Queries\UnifiedContractQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCompanyNamesOfSubmittedContracts(UnifiedContractQueries $queries):JsonResponse
    {
        return response()->json(
            ContractCompanyNameCollection::make($queries->uniqueCompanyNamesOfSubmittedContractsQuery()->get())
        );
    }

    /**
     * Search contract customer entities.
     *
     * @param \App\Http\Requests\Elasticsearch\EntityLookup $request
     * @param \App\Services\Contract\ElasticContractLookupService $lookupService
     * @param string $query
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchContractCustomers(EntityLookup $request,
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
     *
     * @param \App\Http\Requests\Elasticsearch\EntityLookup $request
     * @param \App\Services\Contract\ElasticContractLookupService $lookupService
     * @param string $query
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchContractCompanies(EntityLookup $request,
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
     *
     * @param \App\Http\Requests\Elasticsearch\EntityLookup $request
     * @param \App\Services\Contract\ElasticContractLookupService $lookupService
     * @param string $query
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchContractNumbers(EntityLookup $request,
                                          ElasticContractLookupService $lookupService,
                                          string $query = ''): JsonResponse
    {
        $result = $lookupService->performContractNumbersLookup($query);

        return response()->json(
            $request->collapseElasticsearchResult($result)
        );
    }
}