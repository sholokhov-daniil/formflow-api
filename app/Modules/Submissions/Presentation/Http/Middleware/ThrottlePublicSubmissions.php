<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Middleware;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Ограничивает частоту публичных отправок формы. */
final readonly class ThrottlePublicSubmissions
{
    private const int MAX_ATTEMPTS = 3;

    private const int WINDOW_SECONDS = 60;

    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $form = $request->attributes->get('publicForm');

        if (! $form instanceof Form) {
            return response()->json(['message' => 'The public form is unavailable.'], Response::HTTP_NOT_FOUND);
        }

        $key = 'public:submissions:'.hash('sha256', $form->getKey().'|'.$request->ip());

        try {
            if ($this->limiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                $retryAfter = max(1, $this->limiter->availableIn($key));

                return $this->limitedResponse($retryAfter);
            }

            $this->limiter->hit($key, self::WINDOW_SECONDS);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(
                ['message' => 'Submission service is temporarily unavailable.'],
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Retry-After' => '30'],
            );
        }

        return $next($request);
    }

    private function limitedResponse(int $seconds): JsonResponse
    {
        return response()->json(
            [
                'message' => 'Too many submissions. Please try again later.',
                'retry_after' => $seconds,
            ],
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => (string) $seconds],
        );
    }
}
