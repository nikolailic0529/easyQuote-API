* [The dashboard statistics](#the-dashboard-statistics)
    * [Basic statistics](#basic-statistics)
    * [Top customers](#top-customers)
* [The recent records](#the-recent-records)
    * [Recent Sales Orders](#recent-sales-orders)
    * [Recent Opportunities](#recent-opportunities)
    * [Recent Drafted Quotes](#recent-drafted-quotes)
    * [Recent Submitted Quotes](#recent-submitted-quotes)
    * [Expiring Quotes](#expiring-quotes)

# The dashboard statistics

## Basic statistics

    Get aggregation by existing records in the application.

    [GET/POST] api/stats

    Payload (optional):
    {
        "start_date": {Y-m-d},
        "end_date": {Y-m-d},
        "country_id": {country_uuid},
        "currency_id": {currency_uuid},
    }

    Response:
    {
        "totals": {
            "drafted_quotes_count",
            "submitted_quotes_count",
            "drafted_quotes_value",
            "submitted_quotes_value",
            "dead_quotes_count",
            "dead_quotes_value",
            "expiring_quotes_count",
            "expiring_quotes_value",
            "customers_count",
            "locations_total",
            "assets_renewals_count",
            "assets_renewals_value",
            "assets_count",
            "assets_value",
            "assets_locations_count",
            "drafted_sales_orders_value",
            "submitted_sales_orders_value",
            "drafted_sales_orders_count",
            "submitted_sales_orders_count",
            "lost_opportunities_count",
            "lost_opportunities_value",
            "opportunities_count",
            "opportunities_value",
        },
        "period": {
            "start_date",
            "end_date",
        },
        "base_rate",
        "base_currency",
    }

## Top customers

    [GET/POST] api/stats/customers

    Payload (optional):
    {
        "start_date": {Y-m-d},
        "end_date": {Y-m-d},
        "country_id": {country_uuid},
        "currency_id": {currency_uuid},
    }

    Response:
    [
        "*": {
            "company_id",
            "company_name",
            "total_value",
            "total_count",
        }
    ]

# The recent records

## Recent Sales Orders

    [GET] api/sales-orders/submitted

    Response:
    {
        "data": [
            "*": {
                "id",
                "user_id",
                "contract_type_id",
                "worldwide_quote_id",
                "opportunity_id",
                "order_number",
                "order_date",
                "status",
                "failure_reason",
                "status_reason",
                "customer_name",
                "company_name",
                "rfq_number",
                "order_type",
                "created_at",
                "activated_at",
            },
        ],
        "links": {
            "first",
            "last",
            "prev",
            "next",
        },
        "meta": {
            "current_page",
            "from",
            "last_page",
            "links": [
                "*": {
                    "url",
                    "label",
                    "active",
                },
            ],
            "path",
            "per_page",
            "to",
            "total",
        }
    }

## Recent Opportunities

    [GET] api/opportunities

    Response:
    {
        "data": [
            "*": {
                "id",
                "user_id",
                "company_id",
                "opportunity_type",
                "status_type",
                "account_name",
                "account_manager_name",
                "opportunity_amount",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                "sale_action_name",
                "project_name",
                "status",
                "status_reason",
                "permissions": {
                    "view",
                    "update",
                    "delete",
                },
                "created_at",
            },
        ],
        "links": {
            "first",
            "last",
            "prev",
            "next",
        },
        "meta": {
            "current_page",
            "from",
            "last_page",
            "links": [
                "*": {
                    "url",
                    "label",
                    "active",
                },
            ],
            "path",
            "per_page",
            "to",
            "total",
        }
    }

## Recent Drafted Quotes

    [GET] api/unified-quotes/drafted

    Response:
    {
        "data": [
            "*": {
                "id",
                "user_id",
                "business_division",
                "contract_type",
                "opportunity_id",
                "customer_id",
                "customer_name",
                "company_name",
                "rfq_number",
                "completeness",
                "sales_order_id",
                "has_sales_order",
                "sales_order_submitted",
                "contract_id",
                "has_contract",
                "contract_submitted_at",
                "active_version_id",
                "has_distributor_files",
                "has_schedule_files",
                "permissions": {
                    "view",
                    "update",
                    "delete",
                },
                "submitted_at",
                "is_submitted",
                "updated_at",
                "activated_at",
                "is_active",
            },
        ],
        "links": {
            "first",
            "last",
            "prev",
            "next",
        },
        "meta": {
            "current_page",
            "from",
            "last_page",
            "links": [
                "*": {
                    "url",
                    "label",
                    "active",
                },
            ],
            "path",
            "per_page",
            "to",
            "total",
        }
    }

## Recent Submitted Quotes

    [GET] api/unified-quotes/submitted

    Response:
    {
        "data": [
            "*": {
                "id",
                "user_id",
                "business_division",
                "contract_type",
                "opportunity_id",
                "customer_id",
                "customer_name",
                "company_name",
                "rfq_number",
                "completeness",
                "sales_order_id",
                "has_sales_order",
                "sales_order_submitted",
                "contract_id",
                "has_contract",
                "contract_submitted_at",
                "active_version_id",
                "has_distributor_files",
                "has_schedule_files",
                "permissions": {
                    "view",
                    "update",
                    "delete",
                },
                "submitted_at",
                "is_submitted",
                "updated_at",
                "activated_at",
                "is_active",
            },
        ],
        "links": {
            "first",
            "last",
            "prev",
            "next",
        },
        "meta": {
            "current_page",
            "from",
            "last_page",
            "links": [
                "*": {
                    "url",
                    "label",
                    "active",
                },
            ],
            "path",
            "per_page",
            "to",
            "total",
        }
    }

## Expiring Quotes

    [GET] api/unified-quotes/expiring

    Response:
    {
        "data": [
            "*": {
                "id",
                "user_id",
                "business_division",
                "contract_type",
                "opportunity_id",
                "customer_id",
                "customer_name",
                "company_name",
                "rfq_number",
                "completeness",
                "valid_until_date",
                "permissions": {
                    "view",
                    "update",
                    "delete",
                },
                "updated_at",
                "activated_at",
                "is_active",
            },
        ],
        "links": {
            "first",
            "last",
            "prev",
            "next",
        },
        "meta": {
            "current_page",
            "from",
            "last_page",
            "links": [
                "*": {
                    "url",
                    "label",
                    "active",
                },
            ],
            "path",
            "per_page",
            "to",
            "total",
        }
    }
