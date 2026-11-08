<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use Illuminate\Http\Request;

interface AuthenticationAttemptIdentityResolver
{
    public function resolve(Request $request): string;
}
