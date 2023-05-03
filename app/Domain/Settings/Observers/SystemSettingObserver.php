<?php

namespace App\Domain\Settings\Observers;

use App\Domain\Settings\Models\SystemSetting;

class SystemSettingObserver
{
    /**
     * Handle the system setting "updated" event.
     *
     * @return void
     */
    public function saved(SystemSetting $systemSetting)
    {
        $systemSetting->cacheValue();
    }
}
