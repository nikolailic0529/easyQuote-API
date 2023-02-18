<?php

namespace App\Domain\UnifiedContract\Queries;

use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\Rescue\Models\Contract;
use App\Domain\UnifiedContract\DataTransferObjects\ContractLookupQueryData;
use App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\FieldValue;
use App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\TermValue;
use App\Domain\UnifiedContract\Services\ElasticContractLookupService;
use App\Foundation\Support\Elasticsearch\ElasticsearchHelper;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnifiedContractQueries
{
    public function __construct(protected ConnectionInterface $connection,
                                protected Elasticsearch $elasticsearch,
                                protected ElasticContractLookupService $lookupService)
    {
    }

    public function uniqueUserNamesOfDraftedContractsQuery(string $column = 'user_fullname'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as $column"),
            ])
            ->join('users', function (JoinClause $join) use ($contractModel) {
                $join->on('users.id', $contractModel->qualifyColumn('user_id'));
            })
            ->whereNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as $column"),
            ])
            ->join('users', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('users.id', $hpeContractModel->qualifyColumn('user_id'));
            })
            ->whereNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'user_names'
        )
            ->distinct($column);
    }

    public function uniqueUserNamesOfSubmittedContractsQuery(string $column = 'user_fullname'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as $column"),
            ])
            ->join('users', function (JoinClause $join) use ($contractModel) {
                $join->on('users.id', $contractModel->qualifyColumn('user_id'));
            })
            ->whereNotNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as $column"),
            ])
            ->join('users', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('users.id', $hpeContractModel->qualifyColumn('user_id'));
            })
            ->whereNotNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'user_names'
        )
            ->distinct($column);
    }

    public function uniqueCustomerNamesOfDraftedContractsQuery(string $column = 'customer_name'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                "customers.name as $column",
            ])
            ->join('customers', function (JoinClause $join) use ($contractModel) {
                $join->on('customers.id', $contractModel->qualifyColumn('customer_id'));
            })
            ->whereNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                DB::raw("NULLIF(json_unquote(json_extract(`{$hpeContractModel->getTable()}`.`sold_contact`, '$.\"org_name\"')), 'null') as $column"),
            ])
            ->whereNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'customer_names'
        )
            ->whereNotNull($column)
            ->distinct($column);
    }

    public function uniqueCustomerNamesOfSubmittedContractsQuery(string $column = 'customer_name'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                "customers.name as $column",
            ])
            ->join('customers', function (JoinClause $join) use ($contractModel) {
                $join->on('customers.id', $contractModel->qualifyColumn('customer_id'));
            })
            ->whereNotNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                DB::raw("NULLIF(json_unquote(json_extract(`{$hpeContractModel->getTable()}`.`sold_contact`, '$.\"org_name\"')), 'null') as $column"),
            ])
            ->whereNotNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'customer_names'
        )
            ->whereNotNull($column)
            ->distinct($column);
    }

    public function uniqueNumbersOfDraftedContractsQuery(string $column = 'contract_number'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                "customers.rfq as $column",
            ])
            ->join('customers', function (JoinClause $join) use ($contractModel) {
                $join->on('customers.id', $contractModel->qualifyColumn('customer_id'));
            })
            ->whereNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                "{$hpeContractModel->qualifyColumn('contract_number')} as $column",
            ])
            ->whereNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'customer_numbers'
        )
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct($column);
    }

    public function uniqueNumbersOfSubmittedContractsQuery(string $column = 'contract_number'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                "customers.rfq as $column",
            ])
            ->join('customers', function (JoinClause $join) use ($contractModel) {
                $join->on('customers.id', $contractModel->qualifyColumn('customer_id'));
            })
            ->whereNotNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                "{$hpeContractModel->qualifyColumn('contract_number')} as $column",
            ])
            ->whereNotNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'customer_numbers'
        )
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct($column);
    }

    public function uniqueCompanyNamesOfDraftedContractsQuery(string $column = 'company_name'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                "companies.name as $column",
            ])
            ->join('companies', function (JoinClause $join) use ($contractModel) {
                $join->on('companies.id', $contractModel->qualifyColumn('company_id'));
            })
            ->whereNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                "companies.name as $column",
            ])
            ->join('companies', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('companies.id', $hpeContractModel->qualifyColumn('company_id'));
            })
            ->whereNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'company_names'
        )
            ->distinct($column);
    }

    public function uniqueCompanyNamesOfSubmittedContractsQuery(string $column = 'company_name'): BaseBuilder
    {
        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                "companies.name as $column",
            ])
            ->join('companies', function (JoinClause $join) use ($contractModel) {
                $join->on('companies.id', $contractModel->qualifyColumn('company_id'));
            })
            ->whereNotNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                "companies.name as $column",
            ])
            ->join('companies', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('companies.id', $hpeContractModel->qualifyColumn('company_id'));
            })
            ->whereNotNull($hpeContractModel->qualifyColumn('submitted_at'));

        return $this->connection->query()->fromSub(
            $rescueContractQuery->toBase()->union($hpeContractQuery->toBase()),
            'company_names'
        )
            ->distinct($column);
    }

    protected function buildContractLookupQueryDataFromRequest(Request $request, bool $submittedFlag = null): ContractLookupQueryData
    {
        $searchQuery = with($request->input('search'), function ($searchQuery) {
            if (false === is_string($searchQuery) || trim($searchQuery) === '') {
                return null;
            }

            return $searchQuery;
        });

        $allowedFields = array_fill_keys([
            'company_name',
            'customer_name',
            'contract_number',
            'user_fullname',
            'created_at',
        ], true);

        $fieldValueFormatter = function (FieldValue $fieldValue) {
            if ($fieldValue->field_name === 'created_at') {
                return;
            }

            $fieldValue->field_value = ElasticsearchHelper::escapeReservedChars($fieldValue->field_value);
        };

        $fieldValidator = function (FieldValue $fieldValue): bool {
            if ($fieldValue->field_name === 'created_at') {
                return Carbon::canBeCreatedFromFormat($fieldValue->field_value, 'Y-m-d');
            }

            return true;
        };

        $fieldValueMapper = function (array $fields) use ($fieldValueFormatter, $fieldValidator, $allowedFields) {
            $fieldValues = [];

            foreach ($fields as $field => $values) {
                if (false === isset($allowedFields[$field])) {
                    continue;
                }

                foreach (Arr::wrap($values) as $value) {
                    if (!is_scalar($value)) {
                        continue;
                    }

                    $fieldValue = new FieldValue([
                        'field_name' => (string) $field,
                        'field_value' => (string) $value,
                    ]);

                    $fieldValueFormatter($fieldValue);

                    if ($fieldValidator($fieldValue)) {
                        $fieldValues[] = $fieldValue;
                    }
                }
            }

            return $fieldValues;
        };

        $termValueMapper = function (array $fields) use ($allowedFields) {
            $terms = [];

            foreach ($fields as $field => $values) {
                if (false === isset($allowedFields[$field])) {
                    continue;
                }

                $termValues = array_map(function ($value) {
                    return (string) $value;
                }, array_values(array_filter(Arr::wrap($values), fn ($value) => is_scalar($value))));

                $terms[] = new TermValue([
                    'term_name' => (string) $field,
                    'term_values' => $termValues,
                ]);
            }

            return $terms;
        };

        $termEqualValues = with(Arr::wrap($request->input('filter.eq')), $termValueMapper);

        $termNotEqualValues = with(Arr::wrap($request->input('filter.ne')), $termValueMapper);

        $gteFields = with(Arr::wrap($request->input('filter.gte')), $fieldValueMapper);

        $lteFields = with(Arr::wrap($request->input('filter.lte')), $fieldValueMapper);

        $mustEqualFields = [];

        if (!is_null($submittedFlag)) {
            $mustEqualFields[] = new FieldValue([
                'field_name' => 'is_submitted',
                'field_value' => $submittedFlag,
            ]);
        }

        return new ContractLookupQueryData([
            'search_query' => $searchQuery,
            'term_equal_values' => $termEqualValues,
            'term_not_equal_values' => $termNotEqualValues,
            'must_not_equal_fields' => [],
            'must_equal_fields' => $mustEqualFields,
            'range_gte_fields' => $gteFields,
            'range_lte_fields' => $lteFields,
        ]);
    }

    protected function isLookupQueryDataEmpty(ContractLookupQueryData $data): bool
    {
        return is_null($data->search_query) &&
            empty($data->should_equal_fields) &&
            empty($data->must_not_equal_fields) &&
            empty($data->term_equal_values) &&
            empty($data->term_not_equal_values) &&
            empty($data->range_gte_fields) &&
            empty($data->range_lte_fields);
    }

    public function paginateUnifiedSubmittedContractsQuery(Request $request = null): BaseBuilder
    {
        $request ??= new Request();

        /** @var \App\Domain\User\Models\User|null $user */
        $user = $request->user();

        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                $contractModel->qualifyColumn('id'),
                $contractModel->qualifyColumn('user_id'),
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_fullname"),
                $contractModel->qualifyColumn('customer_id'),
                'customers.name as customer_name',
                'customers.rfq as customer_rfq_number',
                'customers.valid_until as valid_until_date',
                'customers.support_start as support_start_date',
                'customers.support_end as support_end_date',
                $contractModel->qualifyColumn('company_id'),
                'companies.name as company_name',
                $contractModel->qualifyColumn('quote_id'),
                DB::raw('2 as document_type'),
                $contractModel->qualifyColumn('completeness'),
                $contractModel->qualifyColumn('created_at'),
                $contractModel->qualifyColumn('updated_at'),
                $contractModel->qualifyColumn('activated_at'),
            ])
            ->join('customers', function (JoinClause $join) use ($contractModel) {
                $join->on('customers.id', $contractModel->qualifyColumn('customer_id'));
            })
            ->leftJoin('users', function (JoinClause $join) use ($contractModel) {
                $join->on('users.id', $contractModel->qualifyColumn('user_id'));
            })
            ->leftJoin('companies', function (JoinClause $join) use ($contractModel) {
                $join->on('companies.id', $contractModel->qualifyColumn('company_id'));
            })
            ->whereNotNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                $hpeContractModel->qualifyColumn('id'),
                $hpeContractModel->qualifyColumn('user_id'),
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_fullname"),
                DB::raw('NULL as customer_id'),
                DB::raw("NULLIF(json_unquote(json_extract(`{$hpeContractModel->getTable()}`.`sold_contact`, '$.\"org_name\"')), 'null') as customer_name"),
                "{$hpeContractModel->qualifyColumn('contract_number')} as customer_rfq_number",
                DB::raw('NULL as valid_until_date'),
                DB::raw('NULL as support_start_date'),
                DB::raw('NULL as support_end_date'),
                $hpeContractModel->qualifyColumn('company_id'),
                'companies.name as company_name',
                DB::raw('NULL as quote_id'),
                DB::raw('3 as document_type'),
                $hpeContractModel->qualifyColumn('completeness'),
                $hpeContractModel->qualifyColumn('created_at'),
                $hpeContractModel->qualifyColumn('updated_at'),
                $hpeContractModel->qualifyColumn('activated_at'),
            ])
            ->whereNotNull($hpeContractModel->qualifyColumn('submitted_at'))
            ->leftJoin('users', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('users.id', $hpeContractModel->qualifyColumn('user_id'));
            })
            ->leftJoin('companies', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('companies.id', $hpeContractModel->qualifyColumn('company_id'));
            });

        if (false === is_null($user) && false === $user->hasRole(R_SUPER)) {
            $rescueContractQuery->whereIn($rescueContractQuery->qualifyColumn('user_id'), $user->getModulePermissionProviders('contracts.read')->push($user->getKey()))
                ->orWhereIn($rescueContractQuery->qualifyColumn('quote_id'), $user->getPermissionTargets('quotes.read'));

            $hpeContractQuery->whereIn($hpeContractQuery->qualifyColumn('user_id'), $user->getModulePermissionProviders('contracts.read')->push($user->getKey()));
        }

        with($this->buildContractLookupQueryDataFromRequest($request, true), function (ContractLookupQueryData $data) use ($rescueContractQuery, $hpeContractQuery) {
            if ($this->isLookupQueryDataEmpty($data)) {
                return;
            }

            $result = $this->lookupService->performComplexContractsLookup($data);
            $hits = ElasticsearchHelper::pluckDocumentKeys($result);

            $rescueContractQuery->whereKey($hits);
            $hpeContractQuery->whereKey($hits);
        });

        $unifiedQuery = $rescueContractQuery->unionAll(
            $hpeContractQuery->toBase()
        );

        $unifiedQuery->orderByRaw('activated_at IS NOT NULL DESC');

        return RequestQueryBuilder::for(
            builder: $unifiedQuery,
            request: $request,
        )
            ->allowOrderFields(...[
                'created_at',
                'updated_at',
                'customer_name',
                'company_name',
                'customer_rfq_number',
                'valid_until_date',
                'support_start_date',
                'support_end_date',
                'user_fullname',
                'completeness',
            ])
            ->enforceOrderBy('updated_at', 'desc')
            ->process()
            ->toBase();
    }

    public function paginateUnifiedDraftedContractsQuery(Request $request = null): BaseBuilder
    {
        $request ??= new Request();

        /** @var \App\Domain\User\Models\User|null $user */
        $user = $request->user();

        $contractModel = new Contract();
        $hpeContractModel = new HpeContract();

        $rescueContractQuery = Contract::query()
            ->select([
                $contractModel->qualifyColumn('id'),
                $contractModel->qualifyColumn('user_id'),
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_fullname"),
                $contractModel->qualifyColumn('customer_id'),
                'customers.name as customer_name',
                'customers.rfq as customer_rfq_number',
                'customers.valid_until as valid_until_date',
                'customers.support_start as support_start_date',
                'customers.support_end as support_end_date',
                $contractModel->qualifyColumn('company_id'),
                'companies.name as company_name',
                $contractModel->qualifyColumn('quote_id'),
                DB::raw('2 as document_type'),
                $contractModel->qualifyColumn('completeness'),
                $contractModel->qualifyColumn('created_at'),
                $contractModel->qualifyColumn('updated_at'),
                $contractModel->qualifyColumn('activated_at'),
            ])
            ->join('customers', function (JoinClause $join) use ($contractModel) {
                $join->on('customers.id', $contractModel->qualifyColumn('customer_id'));
            })
            ->leftJoin('users', function (JoinClause $join) use ($contractModel) {
                $join->on('users.id', $contractModel->qualifyColumn('user_id'));
            })
            ->leftJoin('companies', function (JoinClause $join) use ($contractModel) {
                $join->on('companies.id', $contractModel->qualifyColumn('company_id'));
            })
            ->whereNull($contractModel->qualifyColumn('submitted_at'));

        $hpeContractQuery = HpeContract::query()
            ->select([
                $hpeContractModel->qualifyColumn('id'),
                $hpeContractModel->qualifyColumn('user_id'),
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_fullname"),
                DB::raw('NULL as customer_id'),
                DB::raw("NULLIF(json_unquote(json_extract(`{$hpeContractModel->getTable()}`.`sold_contact`, '$.\"org_name\"')), 'null') as customer_name"),
                "{$hpeContractModel->qualifyColumn('contract_number')} as customer_rfq_number",
                DB::raw('NULL as valid_until_date'),
                DB::raw('NULL as support_start_date'),
                DB::raw('NULL as support_end_date'),
                $hpeContractModel->qualifyColumn('company_id'),
                'companies.name as company_name',
                DB::raw('NULL as quote_id'),
                DB::raw('3 as document_type'),
                $hpeContractModel->qualifyColumn('completeness'),
                $hpeContractModel->qualifyColumn('created_at'),
                $hpeContractModel->qualifyColumn('updated_at'),
                $hpeContractModel->qualifyColumn('activated_at'),
            ])
            ->whereNull($hpeContractModel->qualifyColumn('submitted_at'))
            ->leftJoin('users', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('users.id', $hpeContractModel->qualifyColumn('user_id'));
            })
            ->leftJoin('companies', function (JoinClause $join) use ($hpeContractModel) {
                $join->on('companies.id', $hpeContractModel->qualifyColumn('company_id'));
            });

        if (false === is_null($user) && false === $user->hasRole(R_SUPER)) {
            $rescueContractQuery->whereIn($rescueContractQuery->qualifyColumn('user_id'), $user->getModulePermissionProviders('contracts.read')->push($user->getKey()))
                ->orWhereIn($rescueContractQuery->qualifyColumn('quote_id'), $user->getPermissionTargets('quotes.read'));

            $hpeContractQuery->whereIn($hpeContractQuery->qualifyColumn('user_id'), $user->getModulePermissionProviders('contracts.read')->push($user->getKey()));
        }

        with($this->buildContractLookupQueryDataFromRequest($request, false), function (ContractLookupQueryData $data) use ($rescueContractQuery, $hpeContractQuery) {
            if ($this->isLookupQueryDataEmpty($data)) {
                return;
            }

            $result = $this->lookupService->performComplexContractsLookup($data);
            $hits = ElasticsearchHelper::pluckDocumentKeys($result);

            $rescueContractQuery->whereKey($hits);
            $hpeContractQuery->whereKey($hits);
        });

        $unifiedQuery = $rescueContractQuery->unionAll(
            $hpeContractQuery->toBase()
        );

        $unifiedQuery->orderByRaw('activated_at IS NOT NULL DESC');

        return RequestQueryBuilder::for(
            builder: $unifiedQuery,
            request: $request,
        )
            ->allowOrderFields(...[
                'created_at',
                'updated_at',
                'customer_name',
                'company_name',
                'customer_rfq_number',
                'valid_until_date',
                'support_start_date',
                'support_end_date',
                'user_fullname',
                'completeness',
            ])
            ->enforceOrderBy('updated_at', 'desc')
            ->process()
            ->toBase();
    }
}
