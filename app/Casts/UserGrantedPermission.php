<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\{
    Str,
    Collection,
};
use App\Facades\Permission;

class UserGrantedPermission implements CastsAttributes
{
    protected string $module;

    public function __construct(string $module)
    {
        $this->module = $module;    
    }

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return Collection
     */
    public function get($model, $key, $value, $attributes)
    {
        if ($model->name === R_SUPER) {
            return R_RUD;
        }

        return Permission::grantedModuleLevel($this->module, $model);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set($model, $key, $value, $attributes)
    {
        return $value;
    }
}
