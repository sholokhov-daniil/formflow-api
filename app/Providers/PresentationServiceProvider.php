<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Identity\Infrastructure\Security\AuthenticationAttemptLimiter;
use App\Modules\Identity\Presentation\Http\Middleware\ThrottleAuthenticationFailures;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
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
                    keyResolver: static fn (Request $request): string => $request->ip().'|'.(string) $request->getUser(),
                );
            },
        );
    }
}
