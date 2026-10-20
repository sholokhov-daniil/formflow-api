<?php

use App\Modules\Identity\Infrastructure\Security\AuthenticationAttemptLimiter;
use Illuminate\Cache\RateLimiter;

test('the failure window expires from the first failure rather than the latest one', function () {
    $this->freezeTime();
    $limiter = app(AuthenticationAttemptLimiter::class);
    $limiter->recordFailure('member');
    $this->travel(2)->hours();
    $limiter->recordFailure('member');
    $this->travel(1)->hours();

    $limiter->recordFailure('member');
    $limiter->recordFailure('member');

    expect($limiter->retryAfter('member'))->toBe(0);

    $limiter->recordFailure('member');

    expect($limiter->retryAfter('member'))->toBe(10800);
});

test('resetting failures starts a fresh count but does not remove an active block', function () {
    $this->freezeTime();
    $limiter = app(AuthenticationAttemptLimiter::class);
    $limiter->recordFailure('member');
    $limiter->recordFailure('member');

    $limiter->resetFailures('member');
    $limiter->recordFailure('member');
    $limiter->recordFailure('member');

    expect($limiter->retryAfter('member'))->toBe(0);

    $limiter->recordFailure('member');
    $limiter->resetFailures('member');

    expect($limiter->retryAfter('member'))->toBe(10800);
});

test('failures during a block do not extend it and the next block requires three new failures', function () {
    $this->freezeTime();
    $limiter = app(AuthenticationAttemptLimiter::class);
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $limiter->recordFailure('member');
    }
    $this->travel(10799)->seconds();

    $limiter->recordFailure('member');

    expect($limiter->retryAfter('member'))->toBe(1);

    $this->travel(1)->seconds();
    expect($limiter->retryAfter('member'))->toBe(0);

    $limiter->recordFailure('member');
    $limiter->recordFailure('member');
    expect($limiter->retryAfter('member'))->toBe(0);

    $limiter->recordFailure('member');
    expect($limiter->retryAfter('member'))->toBe(10800);
});

test('the failure threshold and block duration can be configured independently', function () {
    $this->freezeTime();
    $limiter = new AuthenticationAttemptLimiter(
        app(RateLimiter::class),
        maxFailures: 2,
        failureWindowSeconds: 10,
        blockSeconds: 30,
    );
    $limiter->recordFailure('member');
    $this->travel(9)->seconds();

    $limiter->recordFailure('member');

    expect($limiter->retryAfter('member'))->toBe(30);

    $this->travel(10)->seconds();

    expect($limiter->retryAfter('member'))->toBe(20);
});
