<?php

namespace App\Services\Opportunity\Models;

final class PipelinerOppMap
{
    const
        CONTRACT_TYPE = ['opportunity_type'],
        ACCOUNT_MANAGER = ['owner'],
        PROJECT_NAME = ['business_partner_name', 'project_name'],
        NATURE_OF_SERVICE = ['nature_of_service'],
        RENEWAL_MONTH = ['renewal_month', 'ren_month'],
        RENEWAL_YEAR = ['renewal_year', 'ren_year'],
        CUSTOMER_STATUS = ['customer_status'],
        END_USER_NAME = ['enduser'],
        HARDWARE_STATUS = ['hw_status'],
        REGION_NAME = ['region'],
        OPPORTUNITY_START_DATE = ['start_date'],
        OPPORTUNITY_END_DATE = ['end_date'],
        OPPORTUNITY_CLOSING_DATE = ['closing_date'],
        BASE_OPPORTUNITY_AMOUNT = ['opportunity_value'],
        OPPORTUNITY_AMOUNT = ['opportunity_value_foreign_value'],
        OPPORTUNITY_AMOUNT_CURRENCY_CODE = ['opportunity_value_currency_code'],
        LIST_PRICE = ['list_price_foreign_value'],
        BASE_LIST_PRICE = ['list_price'],
        LIST_PRICE_CURRENCY_CODE = ['list_price_currency_code'],
        PURCHASE_PRICE = ['purchase_price_foreign_value'],
        BASE_PURCHASE_PRICE = ['purchase_price'],
        PURCHASE_PRICE_CURRENCY_CODE = ['purchase_price_currency_code'],
        RANKING = ['ranking'],
        ESTIMATED_UPSELL_AMOUNT = ['estimated_upsell_amount'],
        ESTIMATED_UPSELL_AMOUNT_CURRENCY_CODE = ['estimated_upsell_amount_currency_code'],
        PERSONAL_RATING = ['personal_rating'],
        MARGIN_VALUE = ['margin'],
        COMPETITION_NAME = ['competition'],
        SERVICE_LEVEL_AGREEMENT_ID = ['sla'],
        SALE_UNIT_NAME = ['sales_unit'],
        DROP_IN = ['drop_in'],
        LEAD_SOURCE_NAME = ['lead_source_name', 'lead_source'],
        HAS_HIGHER_SLA = ['higher_sla'],
        IS_MULTI_YEAR = ['multi_year'],
        HAS_ADDITIONAL_HARDWARE = ['additional_hardware'],
        REMARKS = ['remark'],
        NOTES = ['notes'],
        SALE_ACTION_NAME = ['sales_step'],
        CAMPAIGN_NAME = ['campaign'],
        SUPPLIERS = ['suppliers'];
}
