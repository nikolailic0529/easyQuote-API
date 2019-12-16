<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the default Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => true,

    /*
     * This model will be used to log activity.
     * It should be implements the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \App\Models\System\Activity::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => 'activity_log',

    /*
     * This is the database connection that will be used by the migration and
     * the Activity model shipped with this package. In case it's not set
     * Laravel database.default will be used instead.
     */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),

    'subject_types' => [
        'quote' => \App\Models\Quote\Quote::class,
        'discount' => [
            \App\Models\Quote\Discount\MultiYearDiscount::class,
            \App\Models\Quote\Discount\PrePayDiscount::class,
            \App\Models\Quote\Discount\PromotionalDiscount::class,
            \App\Models\Quote\Discount\SND::class
        ],
        'margin' => \App\Models\Quote\Margin\CountryMargin::class,
        'vendor' => \App\Models\Vendor::class,
        'company' => \App\Models\Company::class,
        'template' => \App\Models\QuoteTemplate\QuoteTemplate::class,
        'address' => \App\Models\Address::class,
        'contact' => \App\Models\Contact::class,
        'user' => \App\Models\User::class,
        'role' => \App\Models\Role::class,
        'setting' => \App\Models\System\SystemSetting::class,
        'invitation' => \App\Models\Collaboration\Invitation::class
    ],
    'types' => ['created', 'updated', 'deleted', 'authenticated'],
    'periods' => [
        'today',
        'yesterday',
        'this_week',
        'last_week',
        'this_month',
        'last_month',
        'this_year'
    ]
];
