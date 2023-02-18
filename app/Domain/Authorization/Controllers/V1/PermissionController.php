<?php

namespace App\Domain\Authorization\Controllers\V1;

use App\Domain\Authorization\Contracts\PermissionBroker;
use App\Domain\Authorization\Events\GrantedModulePermission;
use App\Domain\Authorization\Requests\GrantModulePermissionRequest;
use App\Domain\User\Facades\UserForm;
use App\Domain\User\Resources\V1\UserForm as FormResource;
use App\Foundation\Http\Controller;

class PermissionController extends Controller
{
    protected const FORM_KEY = 'permissions';

    /**
     * Grant specific level module permission for users.
     *
     * @return \Illuminate\Http\Response
     */
    public function grantModulePermission(GrantModulePermissionRequest $request, PermissionBroker $broker)
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
