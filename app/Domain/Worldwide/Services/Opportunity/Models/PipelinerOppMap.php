<?php

namespace App\Domain\Worldwide\Services\Opportunity\Models;

final class PipelinerOppMap
{
    const CONTRACT_TYPE = ['opportunity_type'];
    const ACCOUNT_MANAGER = ['owner'];
    const
        PROJECT_NAME = ['business_partner_name', 'project_name'];
    const NATURE_OF_SERVICE = ['nature_of_service'];
    const
        RENEWAL_MONTH = ['renewal_month', 'ren_month'];
    const
        RENEWAL_YEAR = ['renewal_year', 'ren_year'];
    const CUSTOMER_STATUS = ['customer_status'];
    const END_USER_NAME = ['enduser'];
    const HARDWARE_STATUS = ['hw_status'];
    const REGION_NAME = ['region'];
    const OPPORTUNITY_START_DATE = ['start_date'];
    const IS_OPPORTUNITY_START_DATE_ASSUMED = ['start_date_assumed'];
    const OPPORTUNITY_END_DATE = ['end_date'];
    const IS_OPPORTUNITY_END_DATE_ASSUMED = ['end_date_assumed'];
    const OPPORTUNITY_CLOSING_DATE = ['closing_date'];
    const BASE_OPPORTUNITY_AMOUNT = ['opportunity_value'];
    const OPPORTUNITY_AMOUNT = ['opportunity_value_foreign_value'];
    const OPPORTUNITY_AMOUNT_CURRENCY_CODE = ['opportunity_value_currency_code'];
    const LIST_PRICE = ['list_price_foreign_value'];
    const BASE_LIST_PRICE = ['list_price'];
    const LIST_PRICE_CURRENCY_CODE = ['list_price_currency_code'];
    const PURCHASE_PRICE = ['purchase_price_foreign_value'];
    const BASE_PURCHASE_PRICE = ['purchase_price'];
    const PURCHASE_PRICE_CURRENCY_CODE = ['purchase_price_currency_code'];
    const RANKING = ['ranking'];
    const ESTIMATED_UPSELL_AMOUNT = ['estimated_upsell_amount'];
    const ESTIMATED_UPSELL_AMOUNT_CURRENCY_CODE = ['estimated_upsell_amount_currency_code'];
    const PERSONAL_RATING = ['personal_rating'];
    const MARGIN_VALUE = ['margin'];
    const COMPETITION_NAME = ['competition'];
    const SERVICE_LEVEL_AGREEMENT_ID = ['sla'];
    const SALE_UNIT_NAME = ['sales_unit'];
    const DROP_IN = ['drop_in'];
    const
        LEAD_SOURCE_NAME = ['lead_source_name', 'lead_source'];
    const HAS_HIGHER_SLA = ['higher_sla'];
    const IS_MULTI_YEAR = ['multi_year'];
    const HAS_ADDITIONAL_HARDWARE = ['additional_hardware'];
    const HAS_SERVICE_CREDITS = ['service_credits'];
    const REMARKS = ['remark'];
    const NOTES = ['notes'];
    const SALE_ACTION_NAME = ['sales_step'];
    const CAMPAIGN_NAME = ['campaign'];
    const SUPPLIERS = ['suppliers'];
    const PRIMARY_EMAIL = ['primary_e_mail'];
    const PRIMARY_PHONE = ['primary_phone'];
    const HOME_PAGE = ['home_page'];
    const VENDOR = ['vendor'];
    const IS_RESELLER = ['reseller'];
    const IS_END_USER = ['end_user'];
    const VAT_TYPE = ['vat_type'];
    const VAT = ['vat'];
}
