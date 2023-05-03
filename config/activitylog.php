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
    'activity_model' => \App\Domain\Activity\Models\Activity::class,

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
        'quote' => [
            \App\Domain\Rescue\Models\Quote::class,
            \App\Domain\Rescue\Models\QuoteVersion::class,
        ],
        'worldwide_quote' => \App\Domain\Worldwide\Models\WorldwideQuote::class,
        'opportunity' => \App\Domain\Worldwide\Models\Opportunity::class,
//        'quote_note' => \App\Models\Note\QuoteNote::class,
        'note' => \App\Domain\Note\Models\Note::class,
        'task' => \App\Domain\Task\Models\Task::class,
        'appointment' => \App\Domain\Appointment\Models\Appointment::class,
        'contract' => [
            \App\Domain\Rescue\Models\Contract::class,
            \App\Domain\HpeContract\Models\HpeContract::class,
        ],
        'customer' => \App\Domain\Rescue\Models\Customer::class,
        'discount' => [
            \App\Domain\Discount\Models\MultiYearDiscount::class,
            \App\Domain\Discount\Models\PrePayDiscount::class,
            \App\Domain\Discount\Models\PromotionalDiscount::class,
            \App\Domain\Discount\Models\SND::class,
        ],
        'margin' => \App\Domain\Margin\Models\CountryMargin::class,
        'vendor' => \App\Domain\Vendor\Models\Vendor::class,
        'company' => \App\Domain\Company\Models\Company::class,
        'template' => [
            \App\Domain\Rescue\Models\QuoteTemplate::class,
            \App\Domain\Rescue\Models\ContractTemplate::class,
        ],
        'country' => \App\Domain\Country\Models\Country::class,
        'address' => \App\Domain\Address\Models\Address::class,
        'contact' => \App\Domain\Contact\Models\Contact::class,
        'user' => \App\Domain\User\Models\User::class,
        'role' => \App\Domain\Authorization\Models\Role::class,
        'setting' => \App\Domain\Settings\Models\SystemSetting::class,
        'importable_column' => \App\Domain\QuoteFile\Models\ImportableColumn::class,
        'invitation' => \App\Domain\Invitation\Models\Invitation::class,
    ],
    'types' => [
        'created',
        'updated',
        'deleted',
        'copied',
        'retrieved',
        'submitted',
        'exported',
        'unravel',
        'activated',
        'deactivated',
        'created_version',
        'deleted_version',
        'authenticated',
        'unauthenticated',
    ],
    'periods' => [
        'today',
        'yesterday',
        'this_week',
        'last_week',
        'this_month',
        'last_month',
        'this_year',
    ],
];
