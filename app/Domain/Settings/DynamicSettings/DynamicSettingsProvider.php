<?php

namespace App\Domain\Settings\DynamicSettings;

use App\Domain\Settings\Models\SystemSetting;

interface DynamicSettingsProvider
{
    public function __invoke(): SystemSetting;
}
