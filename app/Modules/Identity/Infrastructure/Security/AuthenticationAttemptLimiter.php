<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Security;

use Illuminate\Cache\RateLimiter;

/**
 * Ограничитель неудачных попыток входа
 */
class AuthenticationAttemptLimiter
{
    public function __construct(
        protected RateLimiter $limiter,
        protected int $maxFailures = 3,
        protected int $failureWindowSeconds = 10800,
        protected int $blockSeconds = 10800,
    ) {}

    /**
     * Возвращает остаток блокировки в секундах или 0
     *
     * @param string $key
     *
     * @return int
     */
    public function retryAfter(string $key): int
    {
        $blockedKey = $key.':blocked';

        if (! $this->limiter->tooManyAttempts($blockedKey, 1)) {
            return 0;
        }

        return max(1, $this->limiter->availableIn($blockedKey));
    }

    /**
     * Учитывает ошибку входа и блокирует при достижении лимита
     *
     * @param string $key
     *
     * @return void
     */
    public function recordFailure(string $key): void
    {
        if ($this->retryAfter($key) > 0) {
            return;
        }

        $failures = $this->limiter->hit($key.':failures', $this->failureWindowSeconds);

        if ($failures < $this->maxFailures) {
            return;
        }

        $this->limiter->hit($key.':blocked', $this->blockSeconds);

        $this->resetFailures($key);
    }

    /**
     * Сбрасывает счётчик ошибок входа
     *
     * @param string $key
     *
     * @return void
     */
    public function resetFailures(string $key): void
    {
        $this->limiter->clear($key.':failures');
    }
}
