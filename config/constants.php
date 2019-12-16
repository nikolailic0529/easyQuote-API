<?php

return [
    /**
     * Authentication.
     */
    'MCP_00' => 'Your password has been expired, please visit your profile page to change it.',
    'LO_00' => 'You have been logged out due inactivity.',
    'AU_00' => 'User is already authenticated on another device.',

    /**
     * User.
     */
    'PRE_01' => 'Your Password Reset link is expired, please contact your line-manager to resend the link.',
    'USD_01' => 'You could not delete yourself.',

    /**
     * Invitation.
     */
    'IE_01' => 'Your invitation link is expired, please contact your line-manager to resend the invitation.',

    /**
     * Quote.
     */
    'EQ_NF_01' => 'Quote not found for the provided RFQ #',
    'QSE_01' => 'An activated submitted Quote with the same RFQ number already exists.',
    'QNT_01' => 'Please set a Template for the Quote before Importing.',

    /**
     * Quote Template.
     */
    'QTAD_01' => 'You could not delete this Template because it is already in use in one or more Quotes.',
    'QTNF_01' => 'No any Quote Templates found',
    'QTSU_01' => 'You could not update the system defined Template.',
    'QTSD_01' => 'You could not delete the system defined Template.',

    /**
     * Quote File.
     */
    'QFTNS_01' => 'This file type is not supported.',
    'QFNR_01' => 'No rows found in the provided Quote File.',
    'QFNS_01' => 'No data found in the provided Payment Schedule File, try to choose another page.',
    'QFWS_01' => 'It seems you\'ve chosen wrong Data Select Separator',
    'QFUH_01' => 'Unknown Header',
    'QFNR_01' => 'The given file isn\'t readable. Please try to re-save it.',
    'QFNC_01' => 'The given file hasn\'t required columns',

    /**
     * Discount.
     */
    'DE_01' => 'The same Discount already exists.',
    'DNF_01' => 'No Discount Defined for your selection, please contact administrator.',

    /**
     * Company.
     */
    'CPE_01' => 'The company with the same Name or VAT already exists.',
    'CPUD_01' => 'You could not delete this Company because it is already in use.',
    'CPSD_01' => 'You could not delete the system defined Company.',

    /**
     * Vendor.
     */
    'VE_01' => 'The vendor with the same Short Code or Name already exists.',
    'VSU_01' => 'You could not update the system defined Vendor.',
    'VSD_01' => 'You could not delete the system defined Vendor.',
    'VUD_01' => 'You could not delete this Vendor because it is already in use.',

    /**
     * Margin.
     */
    'ME_01' => 'The same Margin already exists.',
    'MNF_01' => 'No Margin Defined for your selection, please contact administrator.',

    /**
     * Role.
     */
    'RSU_01' => 'You could not update the system defined Role.',
    'RSD_01' => 'You could not delete the system defined Role.'
];
