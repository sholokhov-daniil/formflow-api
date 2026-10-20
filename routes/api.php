<?php

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Presentation\Http\Controller\ProjectController;
use App\Modules\Identity\Presentation\Http\Middleware\ThrottleAuthenticationFailures;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Presentation\Http\Controller\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('workspaces', WorkspaceController::class)
        ->middleware([
            ThrottleAuthenticationFailures::class,
            'auth.basic',
        ])
        ->middlewareFor('index', 'can:viewAny,'.Workspace::class)
        ->middlewareFor('store', 'can:create,'.Workspace::class)
        ->middlewareFor('show', 'can:view,workspace')
        ->middlewareFor('update', 'can:update,workspace')
        ->middlewareFor('destroy', 'can:delete,workspace');

    Route::scopeBindings()->group(function (): void {
        Route::apiResource('workspaces.projects', ProjectController::class)
            ->middleware([
                ThrottleAuthenticationFailures::class,
                'auth.basic',
            ])
            ->middlewareFor('index', 'can:viewAny,'.Project::class.',workspace')
            ->middlewareFor('store', 'can:create,'.Project::class.',workspace')
            ->middlewareFor('show', 'can:view,project')
            ->middlewareFor('update', 'can:update,project')
            ->middlewareFor('destroy', 'can:delete,project');
    });
});
