<?php

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Presentation\Http\Controller\FormController;
use App\Modules\FormDefinition\Presentation\Http\Controller\FormFieldController;
use App\Modules\FormDefinition\Presentation\Http\Controller\ProjectController;
use App\Modules\Identity\Presentation\Http\Middleware\ThrottleAuthenticationFailures;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use App\Modules\Submissions\Presentation\Http\Controller\SubmissionController;
use App\Modules\Submissions\Presentation\Http\Controller\SubmissionValueController;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use App\Modules\Tenancy\Presentation\Http\Controller\WorkspaceController;
use App\Modules\Tenancy\Presentation\Http\Controller\WorkspaceMembershipController;
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
        Route::apiResource('workspaces.projects.forms', FormController::class)
            ->middleware([ThrottleAuthenticationFailures::class, 'auth.basic'])
            ->middlewareFor('index', 'can:viewAny,'.Form::class.',project')
            ->middlewareFor('store', 'can:create,'.Form::class.',project')
            ->middlewareFor('show', 'can:view,form')
            ->middlewareFor('update', 'can:update,form')
            ->middlewareFor('destroy', 'can:delete,form');

        Route::apiResource('workspaces.projects.forms.fields', FormFieldController::class)
            ->middleware([ThrottleAuthenticationFailures::class, 'auth.basic'])
            ->middlewareFor('index', 'can:viewAny,'.FormField::class.',form')
            ->middlewareFor('store', 'can:create,'.FormField::class.',form')
            ->middlewareFor('show', 'can:view,field')
            ->middlewareFor('update', 'can:update,field')
            ->middlewareFor('destroy', 'can:delete,field');

        Route::apiResource('workspaces.projects.forms.submissions', SubmissionController::class)
            ->except(['update'])
            ->middleware([ThrottleAuthenticationFailures::class, 'auth.basic'])
            ->middlewareFor('index', 'can:viewAny,'.Submission::class.',form')
            ->middlewareFor('store', 'can:create,'.Submission::class.',form')
            ->middlewareFor('show', 'can:view,submission')
            ->middlewareFor('destroy', 'can:delete,submission');

        Route::apiResource('workspaces.projects.forms.submissions.values', SubmissionValueController::class)
            ->middleware([ThrottleAuthenticationFailures::class, 'auth.basic'])
            ->middlewareFor('index', 'can:viewAny,'.SubmissionValue::class.',submission')
            ->middlewareFor('store', 'can:create,'.SubmissionValue::class.',submission')
            ->middlewareFor('show', 'can:view,value')
            ->middlewareFor('update', 'can:update,value')
            ->middlewareFor('destroy', 'can:delete,value');

        Route::apiResource('workspaces.memberships', WorkspaceMembershipController::class)
            ->middleware([ThrottleAuthenticationFailures::class, 'auth.basic'])
            ->middlewareFor('index', 'can:viewAny,'.WorkspaceMembership::class.',workspace')
            ->middlewareFor('store', 'can:create,'.WorkspaceMembership::class.',workspace')
            ->middlewareFor('show', 'can:view,membership')
            ->middlewareFor('update', 'can:update,membership')
            ->middlewareFor('destroy', 'can:delete,membership');

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
