<?php namespace App\Repositories\System;

use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;
use App\Models\System\SystemSetting;

class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    protected $systemSetting;

    public function __construct(SystemSetting $systemSetting)
    {
        $this->systemSetting = $systemSetting;
    }

    public function get(string $key)
    {
        $builder = $this->systemSetting->where('key', $key);

        if(!$builder->exists()) {
            return null;
        }
        return $builder->first()->value;
    }
}
