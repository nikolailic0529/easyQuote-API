<?php

namespace App\Services\Settings\DynamicSettingsProviders;

use App\Models\System\SystemSetting;

interface DynamicSettingsProvider
{
    public function __invoke(): SystemSetting;
}