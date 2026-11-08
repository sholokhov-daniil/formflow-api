<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use Illuminate\Http\Request;

final class RequestAuthenticationAttemptIdentityResolver implements AuthenticationAttemptIdentityResolver
{
    public function resolve(Request $request): string
    {
        return $request->ip().'|'.(string) $request->getUser();
    }
}
