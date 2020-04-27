<?php

namespace App\Http\Controllers\API;

use App\Contracts\Services\PermissionBroker;
use App\Events\Permission\GrantedModulePermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\GrantModulePermission;
use App\Facades\UserForm;
use App\Http\Resources\User\UserForm as FormResource;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected const FORM_KEY = 'permissions';

    /**
     * Grant specific level module permission for users.
     *
     * @return \Illuminate\Http\Response
     */
    public function grantModulePermission(GrantModulePermission $request, PermissionBroker $broker)
    {
        $result = $broker->grantModulePermission(
            $request->users,
            $request->module,
            $request->level
        );

        return response()->json(tap($result, function ($result) use ($request) {
            UserForm::updateForm([static::FORM_KEY, $request->module], $request->validated());
            event(new GrantedModulePermission($result));
        }));
    }

    public function showModulePermissionForm(string $module)
    {
        return response()->json(
            FormResource::make(
                UserForm::getForm([static::FORM_KEY, $module])
            )
        );
    }
}
