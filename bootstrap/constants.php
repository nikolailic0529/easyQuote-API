<?php

/**
 * Authentication.
 */
define('MCP_00', 'Your password has been expired, please visit your profile page to change it.');
define('LO_00', 'You have been logged out due inactivity.');

define('AU_00', 'You are already authenticated with your account on another device.');
define('AU_01', 'You are already authenticated in another account.');

/**
 * User.
 */
define('PRE_01', 'Your Password Reset link is expired, please contact your line-manager to resend the link.');
define('USD_01', 'You could not delete yourself.');
define('AEU_01', 'You could not update an Administrator\'s email.');
define('PWDE_01', 'Your password is expiring on :expires_at.');
define('PWDE_02', "Your password is expiring on :expires_at. It's strongly recommended to change password.");
define('PWDC_01', 'You have successfully changed your password.');
define('UN_01', 'user-notifications');
define('UN_PWD_EXP', 7);
define('UN_FALLBACK_ROUTE', 'users.notifications');
define('ENF_PWD_CHANGE_DAYS', 30);
define('AT_01', 'Some one tried to login to your account from ip address: :ip_address.');
define('AT_THROTTLE_TIME', 15);

/**
 * Activity.
 */
define('ANF_01', 'No activities found.');

/**
 * Invitation.
 */
define('IE_01', 'Your invitation link is expired, please contact your line-manager to resend the invitation.');

/**
 * Quote.
 */
define('EQ_NF_01', 'Quote not found for the provided RFQ #');
define('QSE_01', 'An activated submitted Quote with the same RFQ number already exists.');
define('QNT_01', 'Please specify a template for the Quote before import.');
define('QNT_02', 'Please specify a template for the Quote before export.');
define('QNF_01', 'Quote not found.');
define('QSU_01', 'You could not update a submitted Quote.');
define('QUC_01', 'You could not change a Customer for a given Quote.');
define('QSS_01', 'Quote has been successfully submitted.');
define('QSS_02', 'Quote with RFQ :rfq_number has been successfully submitted.');
define('Q_RFQE_01', 'Quote with the given RFQ number already exists.');
define('QD_01', 'Quote with RFQ :rfq_number has been deleted.');
define('QDS_01', 'Quote with RFQ :rfq_number has been successfully moved to drafted.');
define('QSF_01', 'Quote submission was failed.');
define('QE_01', 'Quote with RFQ :rfq_number expires at :expires_at.');
define('QE_02', 'expiring-quotes');
define('QG_FTNF_01', 'From or To Group Description is not found.');
define('QG_NF_01', 'The Group Description is not found.');
define('QV_SD_01', 'You could not delete the given Version as the Quote is already submitted.');

define('QCE_01', 'Contract for the given Quote already exists.');
define('QCE_UN_01', 'A contract exists for this quote, please delete the contract first to undo this quote');
define('QCE_D_01', 'A contract exists for this quote, please delete the contract first to delete this quote');
define('QSC_01', 'Quote RFQ %s has been calculated. Total price %s');
define('QSC_ERR_01', 'An error occured when Quote calculation');
define('QTC_S_01', '*** Quote totals calculation started ***');
define('QTC_F_01', '*** Quote totals calculation successfully finished ***');
define('QTC_ERR_01', 'An error occured when quote totals calculation');

define('QLTC_S_01', '*** Quote location totals calculation started ***');
define('QLTC_F_01', '*** Quote location totals calculation successfully finished ***');
define('QLTC_ERR_01', 'An error occured when quote location totals calculation');

/**
 * Contracts.
 */
define('CTSS_01', 'Contract has been successfully submitted.');
define('CTSS_02', 'Contract with number :contract_number has been successfully submitted.');
define('CTSE_01', 'An activated submitted Contract with the same Contract Number already exists.');
define('CTSU_01', 'You could not update a submitted Contract.');
define('CTD_01', 'Contract with number :contract_number has been deleted.');

// HPE Contracts.
define('HPEC_IMPE_01', 'Unable to import the provided file');
define('HPEC_DC_01', 'In order to copy the HPE Contract it must be submitted.');

/**
 * Quote Template.
 */
define('QTAD_01', 'You could not delete this Template because it is already in use in one or more Quotes.');
define('QTNF_01', 'No any Quote Templates found');
define('QTSU_01', 'You could not update the system defined Template.');
define('QTSD_01', 'You could not delete the system defined Template.');
define('QT_TYPE_QUOTE', 1);
define('QT_TYPE_CONTRACT', 2);
define('QT_TYPE_HPE_CONTRACT', 3);
define('QT_TYPES', [QT_TYPE_QUOTE => 'quote', QT_TYPE_CONTRACT => 'contract', QT_TYPE_HPE_CONTRACT => 'hpe_contract']);
define('INV_QT_TYPE', 'Invalid Quote Template Type passed.');

/**
 * Quote File.
 */
define('QFTNS_01', 'This file type is not supported.');
define('QFNRF_01', 'No rows found in the provided Quote File.');
define('QFNRF_02', 'Mandatory data not found. Price or Product Number columns are missing on the page.');
define('QFNS_01', 'No data found in the provided Payment Schedule File, try to choose another page.');
define('QFWS_01', 'It seems you\'ve chosen wrong Data Select Separator');
define('QFUH_01', 'Unknown Header');
define('QFNR_01', 'The given file isn\'t readable. Please try to re-save it.');
define('QFNC_01', 'The given file hasn\'t required columns');
define('QFNE_01', 'Sorry, no files found.');
define('QFT_PL', 'Distributor Price List');
define('QFT_WWPL', 'Worldwide Distributor Price List');
define('QFT_PS', 'Payment Schedule');
define('QFNF_01', 'Quote File not found.');
define('QFNF_02', 'Unable resolve filepath for given QuoteFile instance.');

/**
 * Discount.
 */
define('DE_01', 'Discount with the same Value for the given Country and Vendor already exists.');
define('DE_02', 'Discount with the same Duration for the given Country and Vendor already exists.');
define('DNF_01', 'No Discount Defined for your selection, please contact administrator.');

/**
 * Company.
 */
define('CPE_01', 'The company with the same Name or VAT already exists.');
define('CPUD_01', 'You could not delete this Company because it is already in use.');
define('CPSD_01', 'You could not delete the system defined Company.');
define('CP_DEF_VAT', 'GB758501125');
define('CP_DEF_NAME', 'Support Warehouse Ltd');

/**
 * Customer.
 */
define('CUS_M_01', 'Customer successfully migrated in the external companies.');
define('CUS_ECNE_01', 'External company does not exist. A new company will be saved in database.');
define('CUS_ECE_01', 'External company already exists in database.');
define('CUS_ECS_01', 'External company successfully saved in database.');
define('CUS_ECAC_01', "Customer's addresses and contacts has been attached to external company.");

define('CUSTC_S_01', '*** Customer totals calculation started ***');
define('CUSTC_F_01', '*** Customer totals calculation successfully finished ***');
define('CUSTC_ERR_01', 'An error occured when customer totals calculation');

define('CUSMG_S_01', '*** Customers migration started ***');
define('CUSMG_F_01', '*** Customers migration successfully finished ***');
define('CUSMG_ERR_01', 'An error occured when customers migration');

/**
 * Vendor.
 */
define('VE_01', 'The vendor with the same Short Code or Name already exists.');
define('VSU_01', 'You could not update the system defined Vendor.');
define('VSD_01', 'You could not delete the system defined Vendor.');
define('VUD_01', 'You could not delete this Vendor because it is already in use.');

/**
 * Margin.
 */
define('ME_01', 'The same Margin already exists.');
define('MNF_01', 'No Margin Defined for your selection, please contact administrator.');

/**
 * Role.
 */
define('RSU_01', 'You could not update the system defined Role.');
define('RSD_01', 'You could not delete the system defined Role.');
define('R_SUPER', 'Administrator');
define('R_RUD', 'read,update,delete');
define('ACL_R', 'read');
define('ACL_RU', 'read,update');
define('ACL_RUD', 'read,update,delete');

/**
 * S4.
 */
define('S4_CS_01', 'S4 contract with RFQ :rfq_number was successfully stored.');
define('S4_CS_02', 'Request for S4 contract storing.');
define('S4_CSS_01', 'S4 data has been successfully received.');
define('S4_CSF_01', 'Failed to receive S4 data.');

/**
 * Settings.
 */
define('SS_INV_01', 'The given Setting value is invalid.');
define('SS_INV_02', 'You could not to update this setting as it is read only.');

/**
 * Importable Columns.
 */
define('ICSU_01', 'You could not update the system defined Importable Column.');
define('ICSD_01', 'You could not delete the system defined Importable Column.');

/**
 * Imported rows.
 */
define('IMPR_ERR_01', 'Something went wrong when making row. Row will be imported without columns data.');

/**
 * Country.
 */
define('CSU_01', 'You could not update the system defined Country.');
define('CSD_01', 'You could not delete the system defined Country.');
/** @var string[] */
define('CSRT_01', [
    'GB',
    'US',
    'FR',
    'CA',
    'SE',
    'NL',
    'BE',
    'AF',
    'NO',
    'DK',
    'AT',
    'ZA',
]);


/**
 * Timezone.
 */
define('TZ_DEF_01', '(UTC+01:00) Edinburgh, London');
define('TZ_DEF_02', '(UTC) Edinburgh, London');

/**
 * Location.
 */
define('LC_NF_01', 'Location for provided address not found');
define('LC_FE_01', 'Location already exists in database');
define('LC_FC_01', 'Location found and saved in database');
define('LC_AA_01', 'Location associated with address instance');
define('LC_AUC_01', 'Address locations update completed');

/**
 * Address.
 */
define('ADDR_LCU_S_01', '*** Addresses location update started ***');
define('ADDR_LCU_F_01', '*** Addresses location update successfully finished ***');
define('ADDR_LCU_ERR_01', 'An error occured when addresses location update');

/**
 * Assets.
 */
define('ASSET_MGERR_01', 'Unable migrate quote assets. Assets transaction rolled back.');
define('ASSET_MGSS_01', 'Asset successfully stored in database.');
define('ASSET_MGAE_01', 'Asset already exists in database.');
define('ASSET_MGQF_01', 'Quote assets successfully migrated. Quote marked as assets migrated.');

define('ASSET_MGS_01', '*** Quote assets migration started ***');
define('ASSET_MGF_01', '*** Quote assets migration successfully finished ***');
define('ASSET_MGERR_02', 'An error occured when quote assets migration');

define('ASSET_TCS_01', '*** Asset totals calculation started ***');
define('ASSET_TCF_01', '*** Asset totals calculation successfully finished ***');
define('ASSET_TCERR_01', 'An error occured when asset totals calculation');

/**
 * Service lookup.
 */
define('SL_REQ_01', 'Trying to receive the data using url [%s]');
define('SL_CRE_01', 'Service response exists in the cache. Cached response will be returned.');
define('SL_UR_01', 'Unable to retrieve records from external service');
define('SL_UR_02', 'Unable to build DTO object from the external service response. Possibly unexpected response or data is corrupted.');

/**
 * Misc.
 */
define('THROTTLE_RATE_01', 'throttle:240,1');
define('INV_ARG_RA_01', 'Argument 1 must be an array or an instance of \Illuminate\Http\Request');
define('INV_ARG_QPK_01', 'Argument 1 must be a primary key or an instance of \App\Models\Quote\Quote');
define('INV_ARG_NPK_01', 'Argument 1 must be a primary key or an instance of \App\Models\System\Notification');
define('INV_ARG_SC_01', 'Argument 1 passed to %s() method must be a string or instance of %s.');
define('INV_ARG_UPK_01', 'Argument 2 must be a primary key or an instance of \App\Models\User');
define('INV_ARG_SA_01', 'Argument 1 must be a string or an array.');
define('EQ_UA_01', 'Unauthenticated Request.');
define('EQ_INV_DP_01', 'Invalid Data Provided.');
define('EQ_INV_REQ_01', 'Invalid Request.');
define('EQ_SE_01', 'Server Error.');
define('UNE_01', 'Unknown Error.');
define('MLFQ_01', 'Malformed request.');
define('FFTC_01', 'Failed when flushing Eloquent tagged cache.');
define('TABLE_COUNT_POSTFIX', '_count');
define('ND_01', 'N/A');
define('SUN_01', 'Unable to fetch data from external service');
// MySQL unbuffered connection used for cursors.
define('MYSQL_UNBUFFERED', 'mysql_unbuffered');
define('DB_TA', 5);

/**
 * Business Divisions.
 */
define('BD_RESCUE', '45fc3384-27c1-4a44-a111-2e52b072791e');
define('BD_WORLDWIDE', 'f911cb0b-a1b0-4943-91e7-0a1c796984a1');

/**
 * Contract Types.
 */
define('CT_PACK', 'c4da2cab-7fd0-4f60-87df-2cc9ea602fee');
define('CT_CONTRACT', 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3');

/**
 * User Teams.
 */
define('UT_RESCUE', '6a66a452-c177-4c2f-b1ce-c9f9bbaf4af4');
define('UT_EPD_WW', '297a5395-a579-4190-8ffc-6856af4f5324');

/**
 * Spaces.
 */
define('SP_EPD', '38e1d441-e57a-466f-b60d-7f314f16adc3');

/**
 * Pipelines.
 */
define('PL_WWDP', 'e6a3a7bd-e9cb-4d0f-add7-b7cfc88768ac');

/**
 * Elasticsearch
 */
define('ES_AL_01', 'Elasticsearch nodes are alive.');
define('ES_NAL_01', 'No alive elasticsearch nodes found. Restarting...');

/**
 * Slack.
 */
define('SLACK_SERVICE_URL', 'https://hooks.slack.com/services/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');
// define('SLACK_SERVICE_URL', 'https://hooks.slack.com/services/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'); // dev
define('SNE_01', 'Slack Service did not confirm sending the notification.');
define('SNE_02', 'Failed to send Slack Notification due exception.');
define('SNS_01', 'Slack Notification has been successfully sent.');
define('SN_IMG_QSS', 'img/slack/qss.gif');
define('SN_IMG_QSF', 'img/slack/qsf.gif');
define('SN_IMG_S4RDS', 'img/slack/s4rds.gif');
define('SN_IMG_S4RDF', 'img/slack/s4rdf.gif');
define('SN_IMG_MS', 'img/slack/ms.gif');
define('SN_IMG_MF', 'img/slack/mf.gif');

/**
 * Exchange Rates.
 */
// Exchange Rate Service implementation.
define('ER_SERVICE_CLASS', App\Services\ExchangeRate\HMRCRates::class);
// Exchange Rates update frequency. daily, weekly, monthly.
define('ER_UPD_DEFAULT_SCHEDULE', 'monthly');
define('ER_PARSE_ERR_01', 'An error occured when trying to parse exchange rates.');
define('ER_RECEIVE_ERR_01', 'An error occured when trying to receive exchange rates from %s.');
define('ER_FNE_01', 'File does not exist.');
define('ER_DT_01', '%s will be used as date for exchange rates.');
define('ER_DT_ERR_01', 'Unable parse date from the given file.');
define('ER_SETTING_UPDATE_KEY', 'exchange_rates_update');
define('ER_MARGIN_DEFAULT', 6);

/**
 * Routes.
 */
define('ROUTE_CRUD', ['index', 'show', 'create', 'store', 'update', 'destroy']);
define('ROUTE_CRD', ['index', 'show', 'store', 'destroy']);
define('ROUTE_CR', ['index', 'show', 'store']);
define('ROUTE_RD', ['index', 'show', 'destroy']);
define('ROUTE_RU', ['index', 'show', 'update']);
define('ROUTE_R', ['index', 'show']);

/**
 * Recaptcha.
 */
define('GRC_ERR_01', 'A server error occured while sending request to Google Recaptcha.');
define('GRCS_01', 'Recaptcha skip key has been provided. Verification request skipped.');
