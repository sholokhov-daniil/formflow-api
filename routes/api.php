<?php

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Presentation\Http\Controller\WorkspaceController;
use App\Modules\Identity\Presentation\Http\Middleware\ThrottleAuthenticationFailures;

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('workspaces', WorkspaceController::class)
        ->middleware([
            ThrottleAuthenticationFailures::class,
            'auth.basic',
        ])
        ->middlewareFor('index', 'can:viewAny,' . Workspace::class)
        ->middlewareFor('store', 'can:create,' . Workspace::class)
        ->middlewareFor('show', 'can:view,workspace')
        ->middlewareFor('update', 'can:update,workspace')
        ->middlewareFor('destroy', 'can:delete,workspace');
});
