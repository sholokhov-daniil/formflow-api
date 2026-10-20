<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use Closure;

use App\Modules\Identity\Infrastructure\Security\AuthenticationAttemptLimiter;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ограничение неудачных попыток входа
 */
final readonly class ThrottleAuthenticationFailures
{
    public function __construct(
        private AuthenticationAttemptLimiter $limiter,
        private Closure $keyResolver,
    ) {}

    /**
     * Проверяет блокировку и учитывает результат входа
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $identity = ($this->keyResolver)($request);
        $key = 'auth:attempts:'.hash('sha256', $identity);

        $retryAfter = $this->limiter->retryAfter($key);

        if ($retryAfter > 0) {
            return $this->blockedResponse($retryAfter);
        }

        $response = $next($request);

        if ($request->user() !== null) {
            $this->limiter->resetFailures($key);

            return $response;
        }

        if ($response->getStatusCode() !== Response::HTTP_UNAUTHORIZED) {
            return $response;
        }

        $this->limiter->recordFailure($key);

        $retryAfter = $this->limiter->retryAfter($key);

        if ($retryAfter > 0) {
            return $this->blockedResponse($retryAfter);
        }

        return $response;
    }

    /**
     * Возвращает ответ 429 с остатком блокировки
     *
     * @param int $seconds
     *
     * @return JsonResponse
     */
    private function blockedResponse(int $seconds): JsonResponse
    {
        return response()->json(
            [
                'message' => 'Too many failed authentication attempts.',
                'retry_after' => $seconds,
            ],
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => (string) $seconds],
        );
    }
}
