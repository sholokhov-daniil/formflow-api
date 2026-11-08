<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Identity\Infrastructure\Security\AuthenticationAttemptLimiter;
use App\Modules\Identity\Presentation\Http\Middleware\AuthenticationAttemptIdentityResolver;
use App\Modules\Identity\Presentation\Http\Middleware\RequestAuthenticationAttemptIdentityResolver;
use App\Modules\Identity\Presentation\Http\Middleware\ThrottleAuthenticationFailures;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PresentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ThrottleAuthenticationFailures::class,
            function (Application $app): ThrottleAuthenticationFailures {
                return new ThrottleAuthenticationFailures(
                    limiter: $app->make(AuthenticationAttemptLimiter::class),
                    identityResolver: $app->make(AuthenticationAttemptIdentityResolver::class),
                );
            },
        );

        $this->app->bind(
            AuthenticationAttemptIdentityResolver::class,
            RequestAuthenticationAttemptIdentityResolver::class,
        );
    }
}
