<?php

namespace App\Providers;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Infrastructure\Policies\FormFieldPolicy;
use App\Modules\FormDefinition\Infrastructure\Policies\FormPolicy;
use App\Modules\FormDefinition\Infrastructure\Policies\ProjectPolicy;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use App\Modules\Submissions\Infrastructure\Policies\SubmissionPolicy;
use App\Modules\Submissions\Infrastructure\Policies\SubmissionValuePolicy;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use App\Modules\Tenancy\Infrastructure\Observers\WorkspaceMembershipObserver;
use App\Modules\Tenancy\Infrastructure\Observers\WorkspaceObserver;
use App\Modules\Tenancy\Infrastructure\Policies\WorkspaceMembershipPolicy;
use App\Modules\Tenancy\Infrastructure\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Form::class, FormPolicy::class);
        Gate::policy(FormField::class, FormFieldPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(SubmissionValue::class, SubmissionValuePolicy::class);
        Gate::policy(WorkspaceMembership::class, WorkspaceMembershipPolicy::class);

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        WorkspaceMembership::observe(WorkspaceMembershipObserver::class);
        Workspace::observe(WorkspaceObserver::class);
    }
}
