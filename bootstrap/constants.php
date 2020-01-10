<?php

/**
 * Authentication.
 */
define('MCP_00', 'Your password has been expired, please visit your profile page to change it.');
define('LO_00', 'You have been logged out due inactivity.');
define('AU_00', 'User is already authenticated on another device.');
define('AU_01', 'You are already authenticated in another account.');

/**
 * User.
 */
define('PRE_01', 'Your Password Reset link is expired, please contact your line-manager to resend the link.');
define('USD_01', 'You could not delete yourself.');
define('AEU_01', 'You could not update an Administrator\'s email.');
define('UN_01', 'user-notifications');

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
define('QNT_01', 'Please set a Template for the Quote before Importing.');
define('QNF_01', 'Quote not found.');
define('QSU_01', 'You could not update a submitted Quote.');
define('QUC_01', 'You could not change a Customer for a given Quote.');
define('QSS_01', 'Quote has been successfully submitted.');
define('QSS_02', 'Quote with RFQ :rfq_number has been successfully submitted.');
define('QDS_01', 'Quote with RFQ :rfq_number has been successfully moved to drafted.');
define('QSF_01', 'Quote submission was failed.');
define('QE_01', 'Quote with RFQ :rfq_number expires at :expires_at.');
define('QE_02', 'expiring-quotes');

/**
 * Quote Template.
 */
define('QTAD_01', 'You could not delete this Template because it is already in use in one or more Quotes.');
define('QTNF_01', 'No any Quote Templates found');
define('QTSU_01', 'You could not update the system defined Template.');
define('QTSD_01', 'You could not delete the system defined Template.');

/**
 * Quote File.
 */
define('QFTNS_01', 'This file type is not supported.');
define('QFNRF_01', 'No rows found in the provided Quote File.');
define('QFNS_01', 'No data found in the provided Payment Schedule File, try to choose another page.');
define('QFWS_01', 'It seems you\'ve chosen wrong Data Select Separator');
define('QFUH_01', 'Unknown Header');
define('QFNR_01', 'The given file isn\'t readable. Please try to re-save it.');
define('QFNC_01', 'The given file hasn\'t required columns');

/**
 * Discount.
 */
define('DE_01', 'Discount for the given Country and Vendor with the same value already exists.');
define('DNF_01', 'No Discount Defined for your selection, please contact administrator.');

/**
 * Company.
 */
define('CPE_01', 'The company with the same Name or VAT already exists.');
define('CPUD_01', 'You could not delete this Company because it is already in use.');
define('CPSD_01', 'You could not delete the system defined Company.');

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

/**
 * S4.
 */
define('S4_CS_01', 'S4 contract with RFQ :rfq was successfully stored.');
define('S4_CS_02', 'Request for S4 contract storing.');
define('S4_CSS_01', 'S4 data has been successfully received.');
define('S4_CSF_01', 'Failed to receive S4 data.');

/**
 * Misc.
 */
define('THROTTLE_RATE_01', 'throttle:240,1');
define('INV_ARG_RA_01', 'The 1 passed argument must be an array or an instance of \Illuminate\Http\Request');
define('INV_ARG_QPK_01', 'The 1 passed argument must be a primary key or an instance of \App\Models\Quote\Quote');
define('INV_ARG_NPK_01', 'The 1 passed argument must be a primary key or an instance of \App\Models\System\Notification');
define('INV_ARG_UPK_01', 'The 2 passed argument must be a primary key or an instance of \App\Models\User');
define('INV_ARG_SA_01', 'The 1 passed argument must be a string or an array.');
define('UA_01', 'Unauthenticated Request.');
define('INVDP_01', 'Invalid Data Provided.');
define('UNE_01', 'Unknown Error.');
define('MLFQ_01', 'Malformed request.');
define('FFTC_01', 'Failed when flushing Eloquent tagged cache.');

/**
 * Slack.
 */
define('SLACK_SERVICE_URL', 'https://hooks.slack.com/services/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');
define('SNE_01', 'Slack Service did not confirm sending the notification.');
define('SNE_02', 'Failed to send Slack Notification due exception.');
define('SNS_01', 'Slack Notification has been successfully sent.');
define('SN_IMG_QSS', 'img/slack/qss.gif');
define('SN_IMG_QSF', 'img/slack/qsf.gif');
define('SN_IMG_S4RDS', 'img/slack/s4rds.gif');
define('SN_IMG_S4RDF', 'img/slack/s4rdf.gif');
