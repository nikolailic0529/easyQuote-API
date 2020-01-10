<?php

namespace App\Observers;

use App\Models\System\SystemSetting;

class SystemSettingObserver
{
    /**
     * Handle the system setting "updated" event.
     *
     * @param  \App\Models\System\SystemSetting  $systemSetting
     * @return void
     */
    public function saved(SystemSetting $systemSetting)
    {
        $systemSetting->cacheValue();
    }
}
