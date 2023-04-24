<?php

// Authentication
const MCP_00 = 'Your password has been expired, please visit your profile page to change it.';
const LO_00 = 'You have been logged out due inactivity.';
const AU_00 = 'You are already authenticated with your account on another device.';
const AU_01 = 'You are already authenticated in another account.';

// User
const PRE_01 = 'Your Password Reset link is expired, please contact your line-manager to resend the link.';
const USD_01 = 'You could not delete yourself.';
const UN_FALLBACK_ROUTE = 'users.notifications';
const AT_01 = 'Some one tried to login to your account from ip address: :ip_address.';

// Activity
const ANF_01 = 'No activities found.';

// Invitation
const IE_01 = 'Your invitation link is expired, please contact your line-manager to resend the invitation.';

// Quote
const EQ_NF_01 = 'Quote not found for the provided RFQ #';
const QSE_01 = 'An activated submitted Quote with the same RFQ number already exists.';
const QNT_02 = 'Please specify a template for the Quote before export.';
const QSU_01 = 'You could not update a submitted Quote.';
const QUC_01 = 'You could not change a Customer for a given Quote.';
const QSS_01 = 'Quote has been successfully submitted.';
const QSS_02 = 'Quote with RFQ :rfq_number has been successfully submitted.';
const Q_RFQE_01 = 'Quote with the given RFQ number already exists.';
const QD_01 = 'Quote with RFQ :rfq_number has been deleted.';
const QDS_01 = 'Quote with RFQ :rfq_number has been successfully moved to drafted.';
const QSF_01 = 'Quote submission was failed.';
const QE_01 = 'Quote with RFQ :rfq_number expires at :expires_at.';
const QG_FTNF_01 = 'From or To Group Description is not found.';
const QG_NF_01 = 'The Group Description is not found.';
const QV_SD_01 = 'You could not delete the given Version as the Quote is already submitted.';
const QCE_UN_01 = 'A contract exists for this quote, please delete the contract first to undo this quote';
const QCE_D_01 = 'A contract exists for this quote, please delete the contract first to delete this quote';

// Contract
const CTSS_01 = 'Contract has been successfully submitted.';
const CTSS_02 = 'Contract with number :contract_number has been successfully submitted.';
const CTSE_01 = 'An activated submitted Contract with the same Contract Number already exists.';
const CTSU_01 = 'You could not update a submitted Contract.';
const CTD_01 = 'Contract with number :contract_number has been deleted.';
const HPEC_IMPE_01 = 'Unable to import the provided file';

// Template
const QTSU_01 = 'You could not update the system defined Template.';
const QTSD_01 = 'You could not delete the system defined Template.';
const QT_TYPE_QUOTE = 1;
const QT_TYPE_CONTRACT = 2;
const QT_TYPE_HPE_CONTRACT = 3;
const QT_TYPES = [QT_TYPE_QUOTE => 'quote', QT_TYPE_CONTRACT => 'contract', QT_TYPE_HPE_CONTRACT => 'hpe_contract'];
const INV_QT_TYPE = 'Invalid Quote Template Type passed.';

// Quote file
const QFNRF_02 = 'Mandatory data not found. Price or Product Number columns are missing on the page.';
const QFNS_01 = 'No data found in the provided Payment Schedule File, try to choose another page.';
const QFNE_01 = 'Sorry, no files found.';
const QFT_PL = 'Distributor Price List';
const QFT_WWPL = 'Worldwide Distributor Price List';
const QFT_PS = 'Payment Schedule';
const QFNF_01 = 'Quote File not found.';
const QFNF_02 = 'Unable resolve filepath for given QuoteFile instance.';

// Discount
const DE_01 = 'Discount with the same Value for the given Country and Vendor already exists.';
const DE_02 = 'Discount with the same Duration for the given Country and Vendor already exists.';
const DNF_01 = 'No Discount Defined for your selection, please contact administrator.';

// Company
const CPE_01 = 'The company with the same Name or VAT already exists.';
const CPSD_01 = 'You could not delete the system defined Company.';
const CP_DEF_VAT = 'GB758501125';
const CP_DEF_NAME = 'Support Warehouse Ltd';

// Customer
const CUS_M_01 = 'Customer successfully migrated in the external companies.';

// Vendor
const VSU_01 = 'You could not update the system defined Vendor.';
const VSD_01 = 'You could not delete the system defined Vendor.';
const VUD_01 = 'You could not delete this Vendor because it is already in use.';

// Margin
const ME_01 = 'The same Margin already exists.';
const MNF_01 = 'No Margin Defined for your selection, please contact administrator.';

// Role
const RSU_01 = 'You could not update the system defined Role.';
const RSD_01 = 'You could not delete the system defined Role.';
const R_SUPER = 'Administrator';
const R_RUD = 'read,update,delete';
const ACL_R = 'read';
const ACL_RU = 'read,update';
const ACL_RUD = 'read,update,delete';

// System 4
const S4_CS_01 = 'S4 contract with RFQ :rfq_number was successfully stored.';
const S4_CS_02 = 'Request for S4 contract storing.';
const S4_CSS_01 = 'S4 data has been successfully received.';
const S4_CSF_01 = 'Failed to receive S4 data.';

// Settings
const SS_INV_01 = 'The given Setting value is invalid.';

// Import
const ICSD_01 = 'You could not delete the system defined Importable Column.';
const IMPR_ERR_01 = 'Something went wrong when making row. Row will be imported without columns data.';

// Country
const CSU_01 = 'You could not update the system defined Country.';
const CSD_01 = 'You could not delete the system defined Country.';
/** @var string[] */
const CSRT_01 = [
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
];

// Timezone
const TZ_DEF_01 = '(UTC+01:00) Edinburgh, London';
const TZ_DEF_02 = '(UTC) Edinburgh, London';

// Location
const LC_NF_01 = 'Location for provided address not found';
const LC_FE_01 = 'Location already exists in database';
const LC_FC_01 = 'Location found and saved in database';
const LC_AA_01 = 'Location associated with address instance';

// Asset
const ASSET_MGQF_01 = 'Quote assets successfully migrated. Quote marked as assets migrated.';

// Service lookup
const SL_REQ_01 = 'Trying to receive the data using url [%s]';
const SL_CRE_01 = 'Service response exists in the cache. Cached response will be returned.';
const SL_UR_01 = 'Unable to retrieve records from external service';

// Misc
const THROTTLE_RATE_01 = 'throttle:240,1';
const INV_ARG_RA_01 = 'Argument 1 must be an array or an instance of \Illuminate\Http\Request';
const INV_ARG_SC_01 = 'Argument 1 passed to %s() method must be a string or instance of %s.';
const INV_ARG_UPK_01 = 'Argument 2 must be a primary key or an instance of \App\Models\User';
const INV_ARG_SA_01 = 'Argument 1 must be a string or an array.';
const EQ_UA_01 = 'Unauthenticated Request.';
const EQ_INV_DP_01 = 'Invalid Data Provided.';
const EQ_INV_REQ_01 = 'Invalid Request.';
const MLFQ_01 = 'Malformed request.';
const ND_01 = 'N/A';
const ND_02 = 'n/a';
const SUN_01 = 'Unable to fetch data from external service';
const DB_TA = 5;

// Business division
const BD_RESCUE = '45fc3384-27c1-4a44-a111-2e52b072791e';
const BD_WORLDWIDE = 'f911cb0b-a1b0-4943-91e7-0a1c796984a1';

// Contract type
const CT_PACK = 'c4da2cab-7fd0-4f60-87df-2cc9ea602fee';
const CT_CONTRACT = 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3';

// User team
const UT_RESCUE = '6a66a452-c177-4c2f-b1ce-c9f9bbaf4af4';
const UT_EPD_WW = '297a5395-a579-4190-8ffc-6856af4f5324';

// Space
const SP_EPD = '38e1d441-e57a-466f-b60d-7f314f16adc3';

// Pipeline
const PL_WWDP = 'e6a3a7bd-e9cb-4d0f-add7-b7cfc88768ac';

// Slack
const SLACK_SERVICE_URL = 'https://hooks.slack.com/services/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';
const SNE_01 = 'Slack Service did not confirm sending the notification.';
const SNE_02 = 'Failed to send Slack Notification due exception.';
const SNS_01 = 'Slack Notification has been successfully sent.';
const SN_IMG_QSS = 'img/slack/qss.gif';
const SN_IMG_QSF = 'img/slack/qsf.gif';
const SN_IMG_S4RDS = 'img/slack/s4rds.gif';
const SN_IMG_S4RDF = 'img/slack/s4rdf.gif';
const SN_IMG_MS = 'img/slack/ms.gif';
const SN_IMG_MF = 'img/slack/mf.gif';

// Exchange rate
const ER_SERVICE_CLASS = App\Domain\ExchangeRate\Services\HMRCRates::class;
// Exchange Rates update frequency. daily, weekly, monthly.
const ER_UPD_DEFAULT_SCHEDULE = 'monthly';
const ER_PARSE_ERR_01 = 'An error occured when trying to parse exchange rates.';
const ER_RECEIVE_ERR_01 = 'An error occured when trying to receive exchange rates from %s.';
const ER_DT_01 = '%s will be used as date for exchange rates.';
const ER_DT_ERR_01 = 'Unable parse date from the given file.';
const ER_SETTING_UPDATE_KEY = 'exchange_rates_update';
const ER_MARGIN_DEFAULT = 6;

// Route shortcut
const ROUTE_CRUD = ['index', 'show', 'create', 'store', 'update', 'destroy'];
const ROUTE_CRD = ['index', 'show', 'store', 'destroy'];
const ROUTE_CR = ['index', 'show', 'store'];
const ROUTE_RD = ['index', 'show', 'destroy'];
const ROUTE_RU = ['index', 'show', 'update'];
const ROUTE_R = ['index', 'show'];

// Recaptcha
const GRC_ERR_01 = 'A server error occured while sending request to Google Recaptcha.';
const GRCS_01 = 'Recaptcha skip key has been provided. Verification request skipped.';
