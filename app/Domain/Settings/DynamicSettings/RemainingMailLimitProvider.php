<?php

namespace App\Domain\Settings\DynamicSettings;

use App\Domain\Settings\Models\SystemSetting;
use App\Foundation\Mail\Services\MailRateLimiter;

class RemainingMailLimitProvider implements DynamicSettingsProvider
{
    public function __construct(
        protected readonly MailRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(): SystemSetting
    {
        return tap(new SystemSetting(), function (SystemSetting $property) {
            $property->section = 'mail';
            $property->key = 'remaining_mail_limit';
            $property->type = 'integer';
            $property->is_read_only = true;
            $property->value = $this->rateLimiter->remaining();
            $property->field_type = 'label';
            $property->syncOriginal();
        });
    }
}
