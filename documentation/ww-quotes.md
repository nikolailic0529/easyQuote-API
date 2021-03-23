* [Opportunities List](#opportunities-list)
* [Drafted Contract Quotes List](#drafted-contract-quotes-list)
* [Submitted Contract Quotes List](#submitted-contract-quotes-list)
* [Drafted Pack Quotes List](#drafted-pack-quotes-list)
* [Submitted Pack Quotes List](#submitted-pack-quotes-list)
* [Quote Management](#quote-management)
    * [Init Quote](#init-quote)
    * [Show Quote State](#show-quote-state)
    * [Allowed Contract Stages](#allowed-contract-stages)
    * [Allowed Pack Stages](#allowed-pack-stages)
    * [Delete WW Quote](#delete-ww-quote)
    * [Process Pack Quote Contacts step](#process-pack-quote-contacts-step)
    * [Pack Assets Management](#pack-assets-management)
        * [Init Pack Asset](#init-pack-asset)
        * [Delete Pack Asset](#delete-pack-asset)
        * [Batch Update Pack Assets](#batch-update-pack-assets---next-stage)
        * [Read Asset Batch File](#read-asset-batch-file)
        * [Import Asset Batch File](#import-asset-batch-file)
    * [Process Pack Quote Assets Review Step](#process-pack-quote-assets-review-step)
    * [Process Pack Quote Margin/Tax step](#process-pack-quote-margintax-step)
    * [Process Pack Quote Discount step](#process-pack-quote-discount-step)
    * [Process Pack Quote Details step](#process-pack-quote-details-step)
    * [Process Contract Quote Import step](#process-contract-quote-import-step)
    * [Process import of WW Distributions](#process-import-of-ww-distributions)
    * [Update mapping of WW Distributions](#update-mapping-of-ww-distributions)
    * [Distributor Rows Management](#distributor-rows-management)
        * [Select Rows or Groups of Distributor Quote](#select-rows-or-groups-of-distributor-quote)
        * [Update the specified Row of Distributor Quote](#update-the-specified-row-of-distributor-quote)
    * [Apply margin/tax on WW Distributions](#apply-margintax-on-ww-distributions)
    * [Show Applicable Discounts](#show-applicable-discounts)
    * [Show Price Summary of Contract Quote after Country Margin & Tax](#show-price-summary-of-contract-quote-after-country-margin--tax)
    * [Show Price Summary of Pack Quote after Country Margin & Tax](#show-price-summary-of-pack-quote-after-country-margin--tax)
    * [Show Price Summary of Contract Quote after Discounts](#show-price-summary-of-contract-quote-after-discounts)
    * [Show Price Summary of Pack Quote after Discounts](#show-price-summary-of-pack-quote-after-discounts)  
    * [Apply discount on WW Distributions](#apply-discount-on-ww-distributions)
    * [Update Details of WW Distributions](#update-details-of-ww-distributions)
    * [Delete WW Distribution](#delete-ww-distribution)
    * [Rows Group Management](#rows-group-management)
        * [Create Rows Group](#create-rows-group)
        * [Update Rows Group](#update-rows-group)
        * [Delete Rows Group](#delete-rows-group)
        * [Move Rows between Groups](#move-rows-between-groups)
        * [Rows Lookup](#rows-lookup)
    * [Notes Management](#notes-management)
        * [Paginate Notes](#paginate-notes)
        * [Create Note](#create-note)
        * [Update Note](#update-note)
        * [Delete Note](#delete-note)
    * [Tasks Management](#tasks-management)
        * [Paginate Tasks](#paginate-tasks)
        * [Create Task](#create-task)
        * [Update Task](#update-task)
        * [Delete Task](#delete-task)
    * [Show Quote Preview Data](#show-quote-preview-data)
    * [Export Submitted Quote](#export-submitted-quote)
    * [Download ZIP of Contract Quote Distributor Files](#download-zip-of-contract-quote-distributor-files)
    * [Download ZIP of Contract Quote Schedule Files](#download-zip-of-contract-quote-schedule-files)
    * [Submit Quote](#submit-quote)
    * [Draft Quote](#draft-quote)
    * [Unravel Quote](#unravel-quote)
    * [Mark Quote as active](#mark-quote-as-active)
    * [Mark Quote as inactive](#mark-quote-as-inactive)

# Opportunities List

    [GET] api/opportunities

# Drafted Contract Quotes List

    [GET] api/ww-quotes/contract/drafted

    order_by_customer_name=asc/desc
    order_by_rfq_number=asc/desc
    order_by_valid_until_date=asc/desc
    order_by_customer_support_start_date=asc/desc
    order_by_customer_support_end_date=asc/desc
    order_by_user_fullname=asc/desc
    order_by_created_at=asc/desc

**Response:**

    {
        "current_page",
        "data": [
            "*": {
                    "id",
                    "user_id",
                    "opportunity_id",
                    "company_id",
                    "completeness",
                    "created_at",
                    "updated_at",
                    "activated_at",
                    "user_fullname",
                    "company_name",
                    "customer_name",
                    "rfq_number",
                    "valid_until_date",
                    "customer_support_start_date",
                    "customer_support_end_date",
                    "permissions": {
                        "view",
                        "update",
                        "delete"
                    }
            }
        ],
        "first_page_url",
        "from",
        "last_page",
        "last_page_url",
        "next_page_url",
        "path",
        "per_page",
        "prev_page_url",
        "to",
        "total"
    }

* * *

# Submitted Contract Quotes List

    [GET] api/ww-quotes/contract/submitted
    
    order_by_customer_name=asc/desc
    order_by_rfq_number=asc/desc
    order_by_valid_until_date=asc/desc
    order_by_customer_support_start_date=asc/desc
    order_by_customer_support_end_date=asc/desc
    order_by_created_at=asc/desc

**Response:**

    {
        "current_page",
        "data": [
            "*": {
                    "id",
                    "user_id",
                    "opportunity_id",
                    "sales_order_id",
                    "has_sales_order",
                    "company_id",
                    "completeness",
                    "created_at",
                    "updated_at",
                    "activated_at",
                    "user_fullname",
                    "company_name",
                    "customer_name",
                    "rfq_number",
                    "valid_until_date",
                    "customer_support_start_date",
                    "customer_support_end_date",
                    "permissions": {
                        "view",
                        "update",
                        "delete"
                    }
            }
        ],
        "first_page_url",
        "from",
        "last_page",
        "last_page_url",
        "next_page_url",
        "path",
        "per_page",
        "prev_page_url",
        "to",
        "total"
    }

* * *

# Drafted Pack Quotes List

    [GET] api/ww-quotes/pack/drafted

    order_by_customer_name=asc/desc
    order_by_rfq_number=asc/desc
    order_by_valid_until_date=asc/desc
    order_by_customer_support_start_date=asc/desc
    order_by_customer_support_end_date=asc/desc
    order_by_user_fullname=asc/desc
    order_by_created_at=asc/desc

**Response:**

    {
        "current_page",
        "data": [
            "*": {
                    "id",
                    "user_id",
                    "opportunity_id",
                    "company_id",
                    "completeness",
                    "created_at",
                    "updated_at",
                    "activated_at",
                    "user_fullname",
                    "company_name",
                    "customer_name",
                    "rfq_number",
                    "valid_until_date",
                    "customer_support_start_date",
                    "customer_support_end_date",
                    "permissions": {
                        "view",
                        "update",
                        "delete"
                    }
            }
        ],
        "first_page_url",
        "from",
        "last_page",
        "last_page_url",
        "next_page_url",
        "path",
        "per_page",
        "prev_page_url",
        "to",
        "total"
    }

* * *

# Submitted Pack Quotes List

    [GET] api/ww-quotes/pack/submitted
    
    order_by_customer_name=asc/desc
    order_by_rfq_number=asc/desc
    order_by_valid_until_date=asc/desc
    order_by_customer_support_start_date=asc/desc
    order_by_customer_support_end_date=asc/desc
    order_by_created_at=asc/desc

**Response:**

    {
        "current_page",
        "data": [
            "*": {
                    "id",
                    "user_id",
                    "opportunity_id",
                    "company_id",
                    "completeness",
                    "created_at",
                    "updated_at",
                    "activated_at",
                    "user_fullname",
                    "company_name",
                    "customer_name",
                    "rfq_number",
                    "valid_until_date",
                    "customer_support_start_date",
                    "customer_support_end_date",
                    "permissions": {
                        "view",
                        "update",
                        "delete"
                    }
            }
        ],
        "first_page_url",
        "from",
        "last_page",
        "last_page_url",
        "next_page_url",
        "path",
        "per_page",
        "prev_page_url",
        "to",
        "total"
    }

* * *

# Quote Management

## Init Quote

    [POST] api/ww-quotes
    
    Payload:
    {
        "opportunity_id": {opportunity_uuid},
        "contract_type": {contract_type_string} ("contract" or "pack")
    }

## Show Quote State

    [GET] api/ww-quotes/{id}

    **Available includes:**
        assets (Pack only)
        assets.machine_address (Pack only)
        assets.machine_address.country (Pack only)

        predefined_discounts (Pack only)
        applicable_discounts (Pack only)

        company
        company.addresses

        opportunity
        opportunity.addresses
        opportunity.address.country
        opportunity.contacts
        opportunity.account_manager
        opportunity.primary_account
        opportunity.primary_account_contact

        worldwide_distributions
        quote_currency
        output_currency
        quote_template

        summary (displays total_price, buy_price, margin_percentage for entire quote)

        worldwide_distributions
        worldwide_distributions.distributor_file
        worldwide_distributions.schedule_file
        worldwide_distributions.vendors
        worldwide_distributions.country
        worldwide_distributions.quote_template
        worldwide_distributions.country_margin
        worldwide_distributions.distribution_currency
        worldwide_distributions.mapping
        worldwide_distributions.mapping_row
        worldwide_distributions.mapped_rows
        
        worldwide_distributions.rows_groups
        worldwide_distributions.rows_groups.rows
        worldwide_distributions.summary (displays total_price, buy_price, margin_percentage for each distribution)

## Allowed Contract Stages

    'Initiated'
    'Import'
    'Mapping'
    'Review'
    'Margin'
    'Discount'
    'Additional Detail'
    'Complete'

## Allowed Pack Stages

    'Initiated'
    'Contacts'
    'Assets Creation'
    'Assets Review'
    'Margin'
    'Discount'
    'Additional Detail'
    'Complete'

## Delete WW Quote

    [DELETE] api/ww-quotes/{id}

## Process Contract Quote Import step

    [POST] api/ww-quotes/{id}/import

    {
        "company_id": {internal_company_uuid}, *
        "quote_template_id": {quote_template_uuid}, *
        "quote_currency_id": {quote_currency_uuid}, *
        "output_currency_id": {output_currency_uuid},
        "quote_expiry_date": {quote_expiry_date}, (Y-m-d) *
        "worldwide_distributions": [
            "*": {
                "id": {distribution_uuid}, *
                "vendors": [ *
                    "*": {vendor_uuid}
                ],
                "addresses": [ *
                    "*": {
                        "id": {address_uuid_or_null},
                        "address_type": {address_uuid_or_null},
                        "address_type": {string}, *
                        "address_1": {string},
                        "address_2": {string},
                        "city": {string},
                        "state": {string},
                        "country_id": {country_uuid}, *
                        "is_default": {bool},
                    }
                ],
                "contacts": [ *
                    "*": {
                        "id": {contact_uuid_or_null}, *
                        "contact_type": {string}, *
                        "first_name": {string}, *
                        "last_name": {string}, *
                        "email": {string},
                        "mobile": {string},
                        "phone": {string},
                        "job_title": {string},
                        "is_verified": {bool},
                        "is_default": {bool},
                    }
                ],
                "country_id": {country_uuid}, *
                "distribution_currency_id": {distribution_currency_uuid}, *
                "buy_price": {numeric_value}, (min: 0, max: 999_999_999) *
                "calculate_list_price": {boolean}, *
                "distribution_expiry_date": {date_string} (Y-m-d) *
            }
        ],
        "stage": {stage_string}, *
    }

## Process import of WW Distributions

    [POST] api/ww-distributions/handle

    {
        "worldwide_distributions": [
            {
                "id": "{ww_distribution_id}",
                "vendor_id": "{vendor_id}",
                "country_id": "{country_id}",
                "distributor_file_id": "{distributor_file_id}",
                "distributor_file_page": 2,
                "distribution_currency_id": "{dist_currency_id}",
                "distribution_expiry_date": "{distribution_expiry_date}" (Y-m-d, greater or equal than Quote Expiry Date),
                "buy_price": 607681.90,
                "calculate_list_price": true
            },
            ...
        ]
    }

## Update mapping of WW Distributions

    [POST] api/ww-distributions/mapping


    {
        "worldwide_distributions": [
            "*": {
                "id": "{ww_distribution_id}",
                "mapping": [
                    "*": {
                        "template_field_id": "{field_id}",
                        "importable_column_id": "{column_id}",
                        "is_default_enabled": {boolean},
                        "is_editable": {boolean}
                    },
                ]
            },
            ...
        ],
        "stage": "Mapping"
    }

## Distributor Rows Management

### Select Rows or Groups of Distributor Quote

    [POST] api/ww-distributions/mapping-review


    {
        "worldwide_distributions": [
            {
                "id": "{ww_distribution_id}",
                "selected_rows": [{uuid}], (an empty array is allowed)
                "reject": {boolean},
                
                "sort_rows_column": {rows_column_name}
                    (Any of: 'product_no', 'description', 'serial_no',
                             'date_from', 'date_to', 'qty', 'price',
                             'pricing_document', 'system_handle',
                             'service_level_description'. Optional.)
                    
                "sort_rows_direction: {sort_direction}
                    (Any of: 'asc', 'desc'. Optional.)

                "selected_groups": [{uuid}], (an empty array is allowed)

                "sort_rows_groups_column: {groups_column_name}
                    (Any of: 'group_name', 'rows_count', 'rows_sum'.
                                                        Optional.)

                "sort_rows_groups_direction: {sort_direction}
                    (Any of: 'asc', 'desc'. Optional.)

                "use_groups": {boolean}
            },
            ...
        ],
        "stage": "Review"
    }

### Update the specified Row of Distributor Quote

    [PATCH] api/ww-distributions/{worldwide_distribution_uuid}/mapped-rows/{mapped_row_uuid}

    Payload:
    
    {
        "product_no": {null_or_string_max_191_chars},
        "description": {null_or_string_max_250_chars},
        "serial_no": {null_or_string_max_191_chars},
        "date_from": {null_or_date_string},
        "date_to": {null_or_date_string},
        "qty": {null_or_integer},
        "price": {null_or_numeric_value},
        "pricing_document": {null_or_string_max_250_chars},
        "system_handle": {null_or_string_max_250_chars},
        "searchable": {null_or_string_max_250_chars},
        "service_level_description": {null_or_string_max_250_chars},
    }

    ** If the field is not present in payload,
    ** it won't be updated on the row.

## Apply margin/tax on WW Distributions

    [POST] api/ww-distributions/margin


    {
        "worldwide_distributions": [
            {
                "id": "{ww_distribution_id}",
                
                "tax_value": {numeric_value}, (Optional.)

                "margin_value": {numeric_value}, (Optional.) 

                "margin_method": {numeric_value},
                    (Any of: 'No Margin', 'Standard'.
                        Only 'No Margin' supported currently.
                        Required when margin_value is not null.)

                "quote_type": {quote_type},
                    (Any of: 'New', 'Renewal'.
                        Required when margin_value is not null.)
            },
            ...
        ],
        "stage": "Margin"
    }

## Show applicable discounts

    [GET] api/ww-distributions/{worldwide_distribution}/applicable-discounts

    Response:
    {
        "multi_year": [
            {
                "id": "01d13499-735a-4877-9e9c-39a87ad9e1a3",
                "name": "MY SE 1.00",
                "durations": {
                    "duration": {
                        "value": "1.00",
                        "duration": 4
                    }
                }
            }
        ],
        "pre_pay": [
            {
                "id": "0746f655-79e3-43a1-ba87-76a3cf40b92f",
                "name": "PP CA 35.00",
                "durations": {
                    "duration": {
                        "value": "35.00",
                        "duration": 3
                    }
                }
            }
        ],
        "promotional": [
            {
                "id": "2440df86-2919-4501-8e6c-f27fe97e5f55",
                "name": "PD NL 28.00",
                "value": "28.00",
                "minimum_limit": "1.00"
            }
        ],
        "snd": [
            {
                "id": "04655f44-1adf-4b6a-b092-2fde715579d1",
                "name": "SN ZA 33.00",
                "value": "33.00"
            }
        ]
    }

## Show Price Summary of Contract Quote after Country Margin & Tax

    [POST] api/ww-quotes/{worldwide_quote_uuid}/contract/country-margin-tax-price-summary

    Payload:

    {
        "worldwide_distributions": [
            "*": {
                "worldwide_distribution_id": {uuid}, *
                "index": {integer},
                "margin_value": {numeric_value_min_0},
                "tax_value": {numeric_value_min_0}
            }
        ]
    }

    Response:
    {
        "worldwide_quote_id",
        "price_summary": {
            "total_price",
            "buy_price",
            "final_total_price",
            "final_margin"
        },
        "worldwide_distributions": [
            "*": {
                "worldwide_distribution_id",
                "index",
                "price_summary": {
                    "total_price",
                    "buy_price",
                    "final_total_price",
                    "final_margin"
                }
            }
        ]
    }

## Show Price Summary of Pack Quote after Country Margin & Tax

    [POST] api/ww-quotes/{worldwide_quote_uuid}/pack/country-margin-tax-price-summary

    Payload:

    {
        "margin_value": {numeric_value_min_0},
        "tax_value": {numeric_value_min_0}
    }

    Response:
    {
        "worldwide_quote_id",
        "price_summary": {
            "total_price",
            "buy_price",
            "final_total_price",
            "final_margin"
        }
    }

## Show Price Summary of Contract Quote after Discounts

    [POST] api/ww-quotes/{worldwide_quote_uuid}/contract/discounts-price-summary

    Payload:

    {
        "worldwide_distributions": [
            "*": {
                "worldwide_distribution_id": {uuid}, *
                "index": {integer},
                "predefined_discounts": {
                    "multi_year_discount": {uuid_or_null},
                    "pre_pay_discount": {uuid_or_null},
                    "promotional_discount": {uuid_or_null},
                    "sn_discount": {uuid_or_null}
                },
                "custom_discount": {numeric_value} (min:0,max:100) // must be null when "predefined_discounts" object is present
            }
        ]
    }

## Show Price Summary of Pack Quote after Discounts

    [POST] api/ww-quotes/{worldwide_quote_uuid}/pack/discounts-price-summary

        Payload:

    {
        "predefined_discounts": {
            "multi_year_discount": {uuid_or_null},
            "pre_pay_discount": {uuid_or_null},
            "promotional_discount": {uuid_or_null},
            "sn_discount": {uuid_or_null}
        },
        "custom_discount": {numeric_value} (min:0,max:100) // must be null when "predefined_discounts" object is present
    }

[comment]: <> (## Show Price Summary after Country Margin & Tax)

[comment]: <> (    [POST] api/ww-distributions/{worldwide_distribution_uuid}/country-margin-tax-margin)

[comment]: <> (    Payload:)

[comment]: <> (    {)

[comment]: <> (        "margin_value": {numeric_value_min_0},)

[comment]: <> (        "tax_value": {numeric_value_min_0})

[comment]: <> (    })

[comment]: <> (    Response:)

[comment]: <> (    {)

[comment]: <> (        "total_price",)

[comment]: <> (        "final_total_price", &#40;Total Price after CountryMargin->Tax->DefinedDiscounts&#41;)

[comment]: <> (        "margin_after_country_margin_tax")

[comment]: <> (    })

[comment]: <> (## Show Price Summary after Predefined Discounts)

[comment]: <> (    [POST] api/ww-distributions/{worldwide_distribution_uuid}/discounts-margin)

[comment]: <> (    Payload:)

[comment]: <> (    {)

[comment]: <> (        "sn_discount": {sn_discount_uuid_or_null} )

[comment]: <> (        "promotional_discount": {promotional_discount_uuid_or_null} )

[comment]: <> (        "multi_year_discount": {multi_year_discount_uuid_or_null} )

[comment]: <> (        "pre_pay_discount": {pre_pay_discount_uuid_or_null} )

[comment]: <> (    })

[comment]: <> (    Response:)

[comment]: <> (    {)

[comment]: <> (        "total_price",)

[comment]: <> (        "final_total_price", &#40;Total Price after Margin->Tax->PredefinedDiscounts&#41;)

[comment]: <> (        "applicable_discounts_value",)

[comment]: <> (        "margin_after_multi_year_discount",)

[comment]: <> (        "margin_after_pre_pay_discount",)

[comment]: <> (        "margin_after_promotional_discount",)

[comment]: <> (        "margin_after_sn_discount")

[comment]: <> (    })

[comment]: <> (## Show Price Summary after Custom Discount)

[comment]: <> (    [POST] api/ww-distributions/{worldwide_distribution_uuid}/custom-discount-margin)

[comment]: <> (    Payload:)

[comment]: <> (    {)

[comment]: <> (        "custom_discount": {numeric_value_min_0})

[comment]: <> (    })

[comment]: <> (    Response:)

[comment]: <> (    {)

[comment]: <> (        "total_price",)

[comment]: <> (        "final_total_price", &#40;Total Price after Margin->Tax->CustomDiscount&#41;)

[comment]: <> (        "applicable_discounts_value",)

[comment]: <> (        "margin_after_custom_discount")

[comment]: <> (    })

## Apply Discount on WW Distributions

    [POST] api/ww-distributions/discounts

    When Predefined Discounts are present, the Custom Discount will be ignored,
    and vice versa.

    Payload:
    {
        "worldwide_distributions": [
            "id": {worldwide_distribution_uuid},
            "predefined_discounts": {
                "multi_year_discount": {uuid_or_null},
                "pre_pay_discount": {uuid_or_null},
                "promotional_discount": {uuid_or_null},
                "sn_discount": {uuid_or_null}
            },
            "custom_discount": {numeric_value} (min:0,max:100)
        ],
        "stage": {quote_stage}
    }

## Update Details of WW Distributions

    [POST] api/ww-distributions/details

    Payload:
    {
        "worldwide_distributions": [
            "id": {worldwide_distribution_uuid},
            "pricing_document": {string_max_1000_chars},
            "service_agreement_id": {string_max_1000_chars},
            "system_handle": {string_max_1000_chars},
            "purchase_order_number": {string_max_250_chars},
            "vat_number": {string_max_250_chars},
            "additional_details": {string_max_10_000_chars}
        ],
        "stage": {stage_string}
    }

## Delete WW Distribution

    [DELETE] api/ww-distributions/{ww_distribution_id}

## Process Pack Quote Contacts Step

    [POST] api/ww-quotes/{worldwide_quote_uuid}/contacts

    Payload:
    {
        "company_id": {company_uuid}, *
        "quote_currency_id": {quote_currency_uuid}, *
        "quote_template_id": {quote_template_uuid}, *
        "quote_expiry_date": {Y-m-d}, *
        "buy_price": {numeric_value}, *
        "addresses": [
            "*": {
                "address_type": {address_type_string} ("Machine" or "Invoice"),
                "address_1" => {string_max_191_chars}, *
                "city" => {string_max_191_chars},
                "state" => {string_max_191_chars},
                "state_code" => {string_max_191_chars},
                "address_2" => {string_max_191_chars},
                "country_id" => {country_uuid},
                "contact_name" => {string_max_191_chars},
                "contact_number" => {string_max_191_chars},
                "contact_email" => {string_max_191_chars},
            }
        ],
        "contacts": [
            "*": {
                "first_name" => {string_max_191_chars}, *
                "last_name" => {string_max_191_chars}, *
                "mobile" => {string_max_191_chars},
                "phone" => {string_max_191_chars},
                "email" => "{string_max_191_chars},
            }
        ],
        "stage": {stage_string} ("Contacts")
    }

## Pack Assets Management

### Init Pack Asset

    [POST] api/ww-quotes/{worldwide_quote_uuid}/assets

    Response:
    {
        "id",
        "worldwide_quote_id",
        ...
    }

### Delete Pack Asset

    [DELETE] api/ww-quotes/{worldwide_quote_uuid}/assets/{asset_uuid}

### Batch Update Pack Assets - Next stage

    [PATCH] api/ww-quotes/{worldwide_quote_uuid}/assets
    
    Payload:
    {
        "assets": [
            {
                "id": {asset_uuid},
                "vendor_id": {vendor_uuid},
                "machine_address_id": {address_uuid_nullable},
                "serial_no": {string_max_191_chars},
                "sku": {string_max_191_chars},
                "product_name": {string_max_191_chars},
                "expiry_date": {date_Y-m-d_format},
                "service_level_description": {string_max_500_chars},
                "price": {numeric},
                "country": {string_2_chars}
            }
        ],
        "stage": {quote_stage} ("Assets")
    }

### Read Asset Batch File

    [POST] api/ww-quotes/{worldwide_quote_uuid}/assets/upload

    Payload:
    {
        "file": {xlsx_csv_file_max_10mb} *
    }

    Response:
    [  
        "file_id",
        "read_rows": [
            "*": {
                "header",
                "header_key",
                "value"
            }
        ]
    ]

### Import Asset Batch File

    [POST] api/ww-quotes/{worldwide_quote_uuid}/assets/import

    Payload:
    {
        "headers": {
            "serial_no": {serial_no_header_key}, (* when a vendor is HPE or LEN)
            "sku": {sku_header_key}, (* when a vendor is HPE or LEN),
            "product_name": {product_name_header_key}, 
            "expiry_date": {expiry_date_header_key},
            "service_level_description": {service_level_header_key},
            "price": {price_header_key},
            "vendor": {vendor_header_key},
            "country": {country_header_key}
        },
        "vendor_id": {vendor_uuid}, *
        "file_id": {file_uuid} *
    }

### Batch Service Lookup

    [POST] api/ww-quotes/{worldwide_quote_uuid}/assets/lookup

    Payload:
    {
        "assets": [
            "*": {
                "id": {asset_uuid},
                "index": {asset_form_index},
                "vendor_short_code": {vendor_short_code} ("LEN" or "HPE"),
                "serial_no": {serial_no},
                "sku": {sku}
            }
        ]
    }
    
    Response: (lookup response is an object with unique asset uuid keys)
    [
        {
            "index",
            "serial_no",
            "model",
            "type",
            "sku",
            "product_name",
            "expiry_date",
            "service_levels": [
                "*": {
                    "description",
                    "price"
                }
            ]
        },
    ]

]

## Process Pack Quote Assets Review step

    [POST] api/ww-quotes/{worldwide_quote_uuid}/assets-review

    Payload:
    {
        "selected_rows": [{uuid}], (an empty array is allowed)
        "reject": {boolean},
        
        "sort_rows_column": {rows_column_name},
            (Any of: 'sku', 'product_name', 'serial_no',
                     'expiry_date', 'price', 'service_level_description', 'vendor_short_code')
            
        "sort_rows_direction: {sort_direction},
            (Any of: 'asc', 'desc'.)

        "stage": {stage_string} ("Assets Review")
    }

## Process Pack Quote Margin/Tax step

    [POST] api/ww-quotes/{worldwide_quote_uuid}/margin

    Payload:
    {
        "tax_value": {numeric_value}, (Optional.)

        "margin_value": {numeric_value}, (Optional.)
        
        "margin_method": {numeric_value},
            (Any of: 'No Margin', 'Standard'.
                Only 'No Margin' supported currently.
                Required when margin_value is not null.)

        "quote_type": {quote_type},
            (Any of: 'New', 'Renewal'.
                Required when margin_value is not null.),
        "stage": {stage_string} ("Margin")
    }

## Process Pack Quote Discount step

    [POST] api/ww-quotes/{worldwide_quote_id}/discounts

    When Predefined Discounts are present, the Custom Discount will be ignored,
    and vice versa.

    Payload:
    {
        "predefined_discounts": {
            "multi_year_discount": {uuid_or_null},
            "pre_pay_discount": {uuid_or_null},
            "promotional_discount": {uuid_or_null},
            "sn_discount": {uuid_or_null}
        },
        "custom_discount": {numeric_value}, (min:0,max:100)
        "stage": {quote_stage} ("Discount")
    }

## Process Pack Quote Details Step

    [POST] api/ww-quotes/{worldwide_quote_id}/details

    Payload:
    {
        "pricing_document": {string_max_1_000_chars} *,
        "service_agreement_id": {string_max_1_000_chars} *,
        "system_handle": {string_max_1_000_chars} *,
        "additional_details": {string_max_10_000_chars}
    }

## Rows Group Management

### Create Rows Group

    [POST] api/ww-distributions/{ww_distribution_id}/rows-groups

    {
        "group_name": {string}, (max 250 characters)
        "search_text": {string}, (max 250 characters)
        "rows": [
            {row_id},
            {row_id}
            ...
        ]
    }

### Update Rows Group

    [PATCH] api/ww-distributions/{ww_distribution_id}/rows-groups/{rows_group_id}

    {
        "group_name": {string}, (max 250 characters)
        "search_text": {string}, (max 250 characters)
        "rows": [
            {row_id},
            {row_id}
            ...
        ]
    }

### Delete Rows Group

    [DELETE] api/ww-distributions/{ww_distribution_id}/rows-groups/{rows_group_id}

### Move Rows between Groups

    [PUT] api/ww-distributions/{ww_distribution_id}/rows-groups

    {
        "output_rows_group_id": {uuid}
        "rows": [
            {row_id},
            {row_id}
            ...
        ],
        "input_rows_group_id": {uuid}
    }

***Response:***

    {
        "output_rows_group": {
            "id",
            "worldwide_distribution_id",
            "rows",
            "rows_sum",
            "rows_count",
            "group_name",
            "search_text",
            "created_at",
            "updated_at",
        },
        "input_rows_group": {
            "id",
            "worldwide_distribution_id",
            "rows",
            "rows_sum",
            "rows_count",
            "group_name",
            "search_text",
            "created_at",
            "updated_at",
        }
    }

### Rows Lookup

    [POST] api/ww-distributions/{ww_distribution_id}/rows-lookup

    {
        "input": {string} (max 250 characters, comma separated values are acceptable),
        "rows_group_id": {uuid}
            (An ID of the group. Optional.)
    }

## Notes Management

## Paginate Notes

    [GET] api/ww-quotes/{worldwide_quote_uuid}/notes

## Create Note

    [POST] api/ww-quotes/{worldwide_quote_uuid}/notes

    Payload:
    {
        "text": {text_max_20_000_chars}
    }

## Update Note

    [PATCH] api/ww-quotes/{worldwide_quote_uuid}/notes/{note_uuid}

    Payload:
    {
        "text": {text_max_20_000_chars}
    }

## Delete Note

    [DELETE] api/ww-quotes/{worldwide_quote_uuid}/notes/{note_uuid}

## Tasks Management

### Paginate Tasks

    [GET] api/ww-quotes/{worldwide_quote_uuid}/tasks

### Create Task

    [POST] api/ww-quotes/{worldwide_quote_uuid}/tasks

    Payload:
    {
        "name": {task_name_string},
        "content": {json_data},
        "expiry_date": {Y-m-d H:i:s},
        "priority": {1|2|3},
        "users": [
            {user_uuid},
            ...
        ]
    }

### Update Task

    [PATCH] api/ww-quotes/{worldwide_quote_uuid}/tasks/{task_uuid}

    Payload:
    {
        "name": {task_name_string},
        "content": {json_data},
        "expiry_date": {Y-m-d H:i:s},
        "priority": {1|2|3},
        "users": [
            {user_uuid},
            ...
        ]
    }

### Delete Task

    [DELETE] api/ww-quotes/{worldwide_quote_uuid}/tasks/{task_uuid}

## Show Quote Preview Data

    [GET] api/ww-quotes/{worldwide_quote_uuid}/preview

    Response:
    {
        "template_data": {
            "first_page_schema": [...],
            "assets_page_schema": [...],
            "payment_schedule_page_schema": [...],
            "last_page_schema": [...],
            "template_assets": {
                "company_logo_x1": "http://URL_TO_IMAGE.png"
                "company_logo_x2": "http://URL_TO_IMAGE.png"
                "company_logo_x3": "http://URL_TO_IMAGE.png"
                "vendor_1_logo_x1": "http://URL_TO_IMAGE.png"
                "vendor_1_logo_x2": "http://URL_TO_IMAGE.png"
                "vendor_1_logo_x3": "http://URL_TO_IMAGE.png"
                "vendor_2_logo_x1": "http://URL_TO_IMAGE.png"
                "vendor_2_logo_x2": "http://URL_TO_IMAGE.png"
                "vendor_2_logo_x3": "http://URL_TO_IMAGE.png"
                ....
            }
        },
        "quote_summary": {
            "company_name": "COMPANY_NAME"
            "rfq_number": "RFQ_NUMBER"
            "quotation_valid_from_date": "d/m/Y"
            "quotation_valid_until_date": "d/m/Y"
            "contact_name": "CONTACT_NAME"
            "contact_email": "CONTACT_EMAIL"
            "contact_phone": "CONTACT_PHONE"
            "distributions_summary": array:2 [
                {
                    "vendor_name": "VENDOR_NAMES_STRING"
                    "country_name": "COUNTRY_NAME"
                    "duration": "d/m/Y - d/m/Y"
                    "quantity": 1
                    "total_price": "####.## £"
                },
                ...
            ]
            +"sub_total_value": "####.## £"
            +"total_value_including_tax": ""
            +"grand_total_value": ""
        },
        "distributions": [
            {
                "vendors": "concatenated_vendors_string",
                "country": "country_name",
                "assets_data": [
                    {
                        "group_name": "GROUP_NAME",
                        "group_total_price": "2384.00 £",
                        "assets": [
                            {
                                "product_no": "PRODUCT_NO"
                                "description": "PRODUCT_DESCRIPTION"
                                "serial_no": "SERIAL_NO"
                                "date_from": "d/m/Y"
                                "date_to": "d/m/Y"
                                "qty": 1
                                "price": "####.## £"
                                "pricing_document": "PRICING_DOCUMENT"
                                "system_handle": "SYSTEM_HANDLE"
                                "searchable": "SEARCHABLE"
                                "service_level_description": "SERVICE_LEVEL_DESCRIPTION"
                            },
                            ...
                        ]
                    }
                ],
                "mapped_fields_count": 7
                "assets_are_grouped": true
                "asset_fields": [
                    {
                        "field_name": "product_no",
                        "field_header": "Product No"
                    },
                    ...
                ],
                "payment_schedule_fields": [
                    {
                        "field_name": "from",
                        "field_header": "From Date"
                    },
                    {
                        "field_name": "to",
                        "field_header": "To Date"
                    },
                    {
                        "field_name": "value",
                        "field_header": "Price"
                    }
                ],
                "payment_schedule_data": [
                    {
                        "from": "d/m/Y"
                        "to": "d/m/Y"
                        "price": "####.## £"
                    },
                    ...
                ],
                "additional_details": "ADDITIONAL_DETAILS_HTML"
            },
            {
                "vendors": "concatenated_vendors_string",
                "country": "country_name",
                "assets_data": [
                    {
                        "product_no": "PRODUCT_NO"
                        "description": "PRODUCT_DESCRIPTION"
                        "serial_no": "SERIAL_NO"
                        "date_from": "d/m/Y"
                        "date_to": "d/m/Y"
                        "qty": 1
                        "price": "####.## £"
                        "pricing_document": "PRICING_DOCUMENT"
                        "system_handle": "SYSTEM_HANDLE"
                        "searchable": "SEARCHABLE"
                        "service_level_description": "SERVICE_LEVEL_DESCRIPTION"
                    },
                    ...
                ],
                "mapped_fields_count": 7
                "assets_are_grouped": false
                "asset_fields": [
                    {
                        "field_name": "product_no",
                        "field_header": "Product No"
                    },
                    ...
                ],
                "payment_schedule_fields": [
                    {
                        "field_name": "from",
                        "field_header": "From Date"
                    },
                    {
                        "field_name": "to",
                        "field_header": "To Date"
                    },
                    {
                        "field_name": "value",
                        "field_header": "Price"
                    }
                ],
                "payment_schedule_data": [],
                "additional_details": "ADDITIONAL_DETAILS_HTML"
            }
        ],
        "pack_assets": [
                "*": {
                    "vendor_short_code": "VENDOR_SHORT_CODE",
                    "product_no": "PRODUCT_NO",
                    "description": "PRODUCT_DESCRIPTION",
                    "serial_no": "SERIAL_NO",
                    "date_to": "d/m/Y",
                    "price": "####.## £",
                    "price_float": "####",
                    "machine_address_string": "MACHINE_ADDRESS"
                }
        ],
        "pack_asset_fields": [
                "*": {
                    "field_name": "value",
                    "field_header": "Price"
                }
        ]
    }

## Submit Quote

    [POST] api/ww-quotes/{worldwide_quote_uuid}/submit

    Payload:
    {
        "quote_closing_date": {Y-m-d},
        "additional_notes": {string_max_10_000_chars}
    }

## Draft Quote

    [POST] api/ww-quotes/{worldwide_quote_uuid}/draft

    Payload:
    {
        "quote_closing_date": {Y-m-d},
        "additional_notes": {string_max_10_000_chars}
    }

## Export Submitted Quote

    [GET] api/ww-quotes/{worldwide_quote_uuid}/export

    Headers:

    content-type: application/pdf
    content-disposition: attachment; filename="{rfq_number}.pdf"

## Download ZIP of Contract Quote Distributor Files

    [GET] api/ww-quotes/{worldwide_quote_uuid}/files/distributor-files

    Headers:

    content-type: application/zip
    content-disposition: attachment; filename="{quote_number}-distributor-files.zip"

## Download ZIP of Contract Quote Schedule Files

    [GET] api/ww-quotes/{worldwide_quote_uuid}/files/schedule-files

    Headers:

    content-type: application/zip
    content-disposition: attachment; filename="{quote_number}-payment-schedule-files.zip"

## Unravel Quote

    [PATCH] api/ww-quotes/{worldwide_quote_uuid}/unravel

## Mark Quote as active

    [PATCH] api/ww-quotes/{worldwide_quote_uuid}/activate

## Mark Quote as inactive

    [PATCH] api/ww-quotes/{worldwide_quote_uuid}/deactivate



