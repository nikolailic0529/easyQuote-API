* [Submitted Sales Orders List](#submitted-sales-orders-list)
* [Drafted Sales Orders List](#drafted-sales-orders-list)
* [Draft Sales Order](#draft-sales-order)
* [Update Sales Order](#update-sales-order)
* [Submit Sales Order](#submit-sales-order)
* [Cancel Sales Order](#cancel-sales-order)
* [Mark Sales Order as Active](#mark-sales-order-as-active)
* [Mark Sales Order as Inactive](#mark-sales-order-as-inactive)
* [Show Sales Order State](#show-sales-order-state)
* [Delete Sales Order](#delete-sales-order)

# Submitted Sales Orders List

    [GET] api/sales-orders/submitted

    order_by_created_at=asc/desc
    order_by_order_type=asc/desc
    order_by_rfq_number=asc/desc
    order_by_customer_name=asc/desc

# Drafted Sales Orders List

    [GET] api/sales-orders/drafted

    order_by_created_at=asc/desc
    order_by_order_type=asc/desc
    order_by_rfq_number=asc/desc
    order_by_customer_name=asc/desc

# Draft Sales Order

    [POST] api/sales-orders
    
    Payload:
    {
        "worldwide_quote_id": {quote_uuid}, *
        "contract_template_id": {template_uuid}, *
        "vat_number": {string_max_191_chars}, *
        "customer_po": {string_max_191_chars} *
    }

# Update Sales Order

    [PATCH] api/sales-orders/{sales_order_uuid}
    
    Payload:
    {
        "contract_template_id": {template_uuid}, *
        "vat_number": {string_max_191_chars}, *
        "customer_po": {string_max_191_chars} *
    }

# Submit Sales Order

    [POST] api/sales-orders/{sales_order_uuid}/submit

# Cancel Sales Order

    [PATCH] api/sales-orders/{sales_order_uuid}/cancel

    Payload:
    {
        "status_reason": {string_max_500_chars} *
    }

# Mark Sales Order as Active

    [PATCH] api/sales-orders/{sales_order_uuid}/activate

# Mark Sales Order as Inactive

    [PATCH] api/sales-orders/{sales_order_uuid}/deactivate

# Show Sales Order State

    [GET] api/sales-orders/{sales_order_uuid}

# Show Sales Order Preview Data

    [GET] api/sales-orders/{sales_order_uuid}/preview

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

# Delete Sales Order

    [DELETE] api/sales-orders/{sales_order_uuid}
