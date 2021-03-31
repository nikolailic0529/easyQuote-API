* [Opportunity Form](#opportunity-form)
    * [Show Opportunity Form Template](#show-opportunity-form-template)
    * [Update Opportunity Form Template](#update-opportunity-form-template)
* [Opportunity Management](#opportunity-management)
    * [Paginate Opportunities](#paginate-opportunities)
    * [Paginate Lost Opportunities](#paginate-lost-opportunities)
    * [Batch Upload Opportunities](#batch-upload-opportunities)
    * [Batch Save Opportunities](#batch-save-opportunities)
    * [Create Opportunity](#create-opportunity)
        * [Paginate Accounts](#paginate-accounts)
        * [Show Account Contacts](#show-account-contacts)
    * [Update Opportunity](#update-opportunity)
    * [Delete Opportunity](#delete-opportunity)
    * [Mark Opportunity as Lost](#mark-opportunity-as-lost)
    * [Restore Opportunity from Lost](#restore-opportunity-from-lost)

# Opportunity Form

*Available Controls*

    primary_account                       : {dynamic}
    primary_account_contact               : {dynamic}
    suppliers_grid                        : {dynamic}
    account_manager                       : {dynamic}
    
    project_name                          : textbox/string
    nature_of_service                     : dropdown/string
    sale_action_name                      : dropdown/string
    customer_status                       : dropdown/string
    end_user_name                         : textbox/string
    hardware_status                       : dropdown/string
    region_name                           : textbox/string
    account_manager_name                  : textbox/string
    campaign_name                         : textbox/string
    service_level_agreement_id            : textbox/string
    sale_unit_name                        : textbox/string
    competition_name                      : textbox/string
    drop_in                               : dropdown/string
    lead_source_name                      : dropdown/string

    opportunity_amount                    : textbox/numeric
    opportunity_amount_currency_code      : dropdown/numeric
    purchase_price                        : textbox/numeric
    purchase_price_currency_code          : dropdown/numeric
    list_price                            : textbox/numeric
    list_price_currency_code              : dropdown/numeric
    estimated_upsell_amount               : textbox/numeric
    estimated_upsell_amount_currency_code : dropdown/numeric
    margin_value                          : textbox/numeric

    renewal_month                         : dropdown/integer (1-12)
    renewal_year                          : dropdown/integer (positive)
    opportunity_start_date                : datepicker/string (y-m-d)
    opportunity_end_date                  : datepicker/string (y-m-d)
    opportunity_closing_date              : datepicker/string (y-m-d)
    expected_order_date                   : datepicker/string (y-m-d)
    customer_order_date                   : datepicker/string (y-m-d)
    purchase_order_date                   : datepicker/string (y-m-d)
    supplier_order_date                   : datepicker/string (y-m-d)
    supplier_order_transaction_date       : datepicker/string (y-m-d)
    supplier_order_confirmation_date      : datepicker/string (y-m-d)

    has_higher_sla                        : checkbox/boolean
    is_multi_year                         : checkbox/boolean
    has_additional_hardware               : checkbox/boolean
    has_service_credits                   : checkbox/boolean

    personal_rating                       : dropdown/string
    ranking                               : dropdown/float(0.0-1.0)
    remarks                               : textbox/string

## Show Opportunity Form Template

    [GET] api/opportunity-template
    
    Response:
    [template schema in json format]

## Update Opportunity Form Template

    [PUT] api/opportunity-template
    
    Payload:
    {
        "form_data": [
            ...
        ]
    }

# Opportunity Management

## Paginate Opportunities

    [GET] api/opportunities

    *Available Orders:
    order_by_account_name=asc/desc
    order_by_opportunity_start_date=asc/desc
    order_by_opportunity_end_date=asc/desc
    order_by_opportunity_closing_date=asc/desc
    order_by_opportunity_amount=asc/desc
    order_by_project_name=asc/desc
    order_by_account_manager_name=asc/desc
    order_by_sale_action_name=asc/desc
    order_by_created_at=asc/desc

    Response:
    {
        "data": [
            "*": {
                "id",
                "account_name",
                "account_manager_name",
                "project_name",
                "opportunity_amount",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                "sale_action_name",
                "created_at",             
            }
        ]
    }

## Paginate Lost Opportunities

    [GET] api/opportunities/lost

    *Available Orders:
    order_by_account_name=asc/desc
    order_by_opportunity_start_date=asc/desc
    order_by_opportunity_end_date=asc/desc
    order_by_opportunity_closing_date=asc/desc
    order_by_opportunity_amount=asc/desc
    order_by_project_name=asc/desc
    order_by_account_manager_name=asc/desc
    order_by_sale_action_name=asc/desc
    order_by_created_at=asc/desc

    Response:
    {
        "data": [
            "*": {
                "id",
                "account_name",
                "account_manager_name",
                "project_name",
                "opportunity_amount",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                "sale_action_name",
                "created_at",             
            }
        ]
    }

## Batch Upload Opportunities

    [POST] api/opportunities/upload

    Payload:
    {
        "file": {xlsx_format_file_max_10mb}
    }

    Response:
    {
        "opportunities": [
            "*" : {
                "id",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                ...
            }
        ],
        "errors": [
            ...
        ]
    }

## Batch Save Opportunities

    [PATCH] api/opportunities/save

    Payload:
    {
        "opportunities": [
            {opportunity_uuid},
            ...
        ]
    }

## Create Opportunity

    [POST] api/opportunities

    {
        "primary_account_id"                    : {company_uuid}
        "primary_account_contact_id"            : {contact_uuid}
        "account_manager_id"                    : {user_uuid}
        "nature_of_service"                     : {string_max_191_chars}
        "sale_action_name"                      : {string_max_191_chars}
        "customer_status"                       : {string_max_191_chars}
        "end_user_name"                         : {string_max_191_chars}
        "hardware_status"                       : {string_max_191_chars}
        "region_name"                           : {string_max_191_chars}

        "account_manager_name"                  : {string_max_100_chars} // remove
        
        "project_name"                          : {string_max_191_chars}
        "campaign_name"                         : {string_max_191_chars}
        "service_level_agreement_id"            : {string_max_191_chars}
        "sale_unit_name"                        : {string_max_191_chars}
        "competition_name"                      : {string_max_191_chars}
        "drop_in"                               : {string_max_191_chars}
        "lead_source_name"                      : {string_max_191_chars}

        "opportunity_amount"                    : {numeric}
        "opportunity_amount_currency_code"      : {string_3_chars}
        "purchase_price"                        : {numeric}
        "purchase_price_currency_code"          : {string_3_chars}
        "list_price"                            : {numeric}
        "list_price_currency_code"              : {string_3_chars}
        "estimated_upsell_amount"               : {numeric}
        "estimated_upsell_amount_currency_code" : {string_3_chars}
        "margin_value"                          : {numeric}
    
        "renewal_month"                         : {integer_between_1-12}
        "renewal_year"                          : {positive_integer}
        "opportunity_start_date"                : {y-m-d}
        "opportunity_end_date"                  : {y-m-d}
        "opportunity_closing_date"              : {y-m-d}
        "expected_order_date"                   : {y-m-d}
        "customer_order_date"                   : {y-m-d}
        "purchase_order_date"                   : {y-m-d}
        "supplier_order_date"                   : {y-m-d}
        "supplier_order_transaction_date"       : {y-m-d}
        "supplier_order_confirmation_date"      : {y-m-d}
    
        "has_higher_sla"                        : {boolean}
        "is_multi_year"                         : {boolean}
        "has_additional_hardware"               : {boolean}
        "has_service_credits"                   : {boolean}
    
        "personal_rating"                       : {integer_between_1-5}
        "remarks"                               : {string_max_10000_chars}

        "suppliers_grid"                        : [
            {
                "supplier_name" : {string_max_191_chars}
                "country_name"  : {string_max_191_chars}
                "contact_name"  : {string_max_191_chars}
                "contact_email" : {string_max_191_chars}
            },
            ...
        ]
    }

### Paginate Accounts

    [GET] api/external-companies
    
    *Available Orders:
    order_by_created_at=asc/desc
    order_by_name=asc/desc
    order_by_vat=asc/desc
    order_by_phone=asc/desc
    order_by_website=asc/desc
    order_by_email=asc/desc
    order_by_category=asc/desc

    Response:
    {
        "data": [
            "*": {
                "id",
                "name",
                "short_code",
                "category",
                "vat",
                "email",
                "phone",
                "website",
                "created_at",
                "activated_at"
            }
        ],
        "links": {
            "first",
            "last",
            "prev",
            "next"
        },
        "meta": {
            "current_page",
            "from",
            "last_page",
            "path",
            "to",
            "total"
        }
    }

### Show Account Contacts

    [GET] api/companies/{account_uuid}
    
    Response:
    {
        "id",
        "user_id",
        "default_vendor_id",
        "default_country_id",
        "default_template_id",
        "is_system",
        "name",
        "category",
        "source",
        "source_long",
        "vat",
        "email",
        "phone",
        "website",
        "addresses": [
            ...
        ],
        "contacts": [
            "*": {
                "id",
                "image_id",
                "email",
                "first_name",
                "last_name",
                "phone",
                "mobile",
                "job_title",
                "is_verified",
                "created_at",
                "updated_at",
                "activated_at",
            }
        ],
        "created_at",
        "activated_at",
    }

## Update Opportunity

    [POST] api/opportunities/{opportunity_uuid}

    {
        "primary_account_id"                    : {company_uuid}
        "primary_account_contact_id"            : {contact_uuid}
        "account_manager_id"                    : {user_uuid}
        "nature_of_service"                     : {string_max_191_chars}
        "sale_action_name"                      : {string_max_191_chars}
        "customer_status"                       : {string_max_191_chars}
        "end_user_name"                         : {string_max_191_chars}
        "hardware_status"                       : {string_max_191_chars}
        "region_name"                           : {string_max_191_chars}

        "account_manager_name"                  : {string_max_100_chars} // remove
        
        "project_name"                          : {string_max_191_chars}
        "campaign_name"                         : {string_max_191_chars}
        "service_level_agreement_id"            : {string_max_191_chars}
        "sale_unit_name"                        : {string_max_191_chars}
        "competition_name"                      : {string_max_191_chars}
        "drop_in"                               : {string_max_191_chars}
        "lead_source_name"                      : {string_max_191_chars}

        "opportunity_amount"                    : {numeric}
        "opportunity_amount_currency_code"      : {string_3_chars}
        "purchase_price"                        : {numeric}
        "purchase_price_currency_code"          : {string_3_chars}
        "list_price"                            : {numeric}
        "list_price_currency_code"              : {string_3_chars}
        "estimated_upsell_amount"               : {numeric}
        "estimated_upsell_amount_currency_code" : {string_3_chars}
        "margin_value"                          : {numeric}
    
        "renewal_month"                         : {integer_between_1-12}
        "renewal_year"                          : {positive_integer}
        "opportunity_start_date"                : {y-m-d}
        "opportunity_end_date"                  : {y-m-d}
        "opportunity_closing_date"              : {y-m-d}
        "expected_order_date"                   : {y-m-d}
        "customer_order_date"                   : {y-m-d}
        "purchase_order_date"                   : {y-m-d}
        "supplier_order_date"                   : {y-m-d}
        "supplier_order_transaction_date"       : {y-m-d}
        "supplier_order_confirmation_date"      : {y-m-d}
    
        "has_higher_sla"                        : {boolean}
        "is_multi_year"                         : {boolean}
        "has_additional_hardware"               : {boolean}
        "has_service_credits"                   : {boolean}
    
        "personal_rating"                       : {integer_between_1-5}
        "remarks"                               : {string_max_10000_chars}

        "suppliers_grid"                        : [
            {
                "id"            : {supplier_uuid}, (if exists)
                "supplier_name" : {string_max_191_chars},
                "country_name"  : {string_max_191_chars},
                "contact_name"  : {string_max_191_chars},
                "contact_email" : {string_max_191_chars},
            },
            ...
        ]
    }

## Delete Opportunity

    [DELETE] api/opportunities/{opportunity_uuid}

## Mark Opportunity as Lost

    [PATCH] api/opportunities/{opportunity_uuid}/lost

    Payload:
    {
        "status_reason": {string_max_500_chars} *
    }

## Restore Opportunity from Lost

    [PATCH] api/opportunities/{opportunity_uuid}/restore-from-lost
