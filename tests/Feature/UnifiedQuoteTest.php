<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UnifiedQuoteTest extends TestCase
{
    /**
     * Test an ability to view paginated unified expiring quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedUnifiedExpiringQuotes()
    {
        $this->authenticateApi();

        $this->getJson('api/unified-quotes/expiring')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'business_division',
                        'contract_type',
                        'opportunity_id',
                        'customer_id',
                        'customer_name',
                        'company_name',
                        'rfq_number',
                        'valid_until_date',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->getJson('api/unified-quotes/expiring?order_by_updated_at=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_rfq_number=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_company_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_customer_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_completeness=desc')
//            ->dump()
            ->assertOk();

    }

    /**
     * Test an ability to view paginated unified submitted quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedUnifiedSubmittedQuotes()
    {
        $this->authenticateApi();

        $this->getJson('api/unified-quotes/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'business_division',
                        'contract_type',
                        'opportunity_id',
                        'customer_id',
                        'customer_name',
                        'company_name',
                        'rfq_number',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'active_version_id',

                        'has_distributor_files',
                        'has_schedule_files',

                        'sales_order_id',
                        'has_sales_order',
                        'sales_order_submitted',
                        'contract_id',
                        'has_contract',
                        'contract_submitted_at',

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->getJson('api/unified-quotes/submitted?order_by_updated_at=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_rfq_number=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_company_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_customer_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_completeness=desc')
//            ->dump()
            ->assertOk();

    }

    /**
     * Test an ability to view paginated unified submitted quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedUnifiedDraftedQuotes()
    {
        $this->authenticateApi();

        $this->getJson('api/unified-quotes/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'business_division',
                        'contract_type',
                        'opportunity_id',
                        'customer_id',
                        'customer_name',
                        'company_name',
                        'rfq_number',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'active_version_id',

                        'has_distributor_files',
                        'has_schedule_files',

                        'sales_order_id',
                        'has_sales_order',
                        'sales_order_submitted',
                        'contract_id',
                        'has_contract',
                        'contract_submitted_at',

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->getJson('api/unified-quotes/drafted?order_by_updated_at=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_rfq_number=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_company_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_customer_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_completeness=desc')
//            ->dump()
            ->assertOk();

    }
}
