<?php

use App\Support\Auth\LoginThrottle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Cache::flush());

it('allows attempts under the limit', function () {
    $t = LoginThrottle::for('admin', 'a@dots.test', '127.0.0.1');

    expect($t->tooManyAttempts())->toBeFalse();
    expect($t->remaining())->toBe(LoginThrottle::MAX_ATTEMPTS);
});

it('locks after five failed attempts for fifteen minutes', function () {
    $t = LoginThrottle::for('admin', 'a@dots.test', '127.0.0.1');

    for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
        $t->hit();
    }

    expect($t->tooManyAttempts())->toBeTrue();
    expect($t->remaining())->toBe(0);
    expect($t->availableIn())->toBeGreaterThan(0)
        ->and($t->availableIn())->toBeLessThanOrEqual(LoginThrottle::DECAY_SECONDS);
});

it('throws a validation error on the email field when locked', function () {
    $t = LoginThrottle::for('customer', 'c@dots.test', '127.0.0.1');
    for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
        $t->hit();
    }

    try {
        $t->ensureNotLocked();
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('email');
    }
});

it('clears the counter', function () {
    $t = LoginThrottle::for('admin', 'a@dots.test', '127.0.0.1');
    $t->hit();
    $t->hit();
    $t->clear();

    expect($t->remaining())->toBe(LoginThrottle::MAX_ATTEMPTS);
});

it('keeps separate counters per guard, identifier and ip', function () {
    $admin = LoginThrottle::for('admin', 'same@dots.test', '127.0.0.1');
    $customer = LoginThrottle::for('customer', 'same@dots.test', '127.0.0.1');
    $otherIp = LoginThrottle::for('admin', 'same@dots.test', '10.0.0.9');

    for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
        $admin->hit();
    }

    expect($admin->tooManyAttempts())->toBeTrue();
    expect($customer->tooManyAttempts())->toBeFalse();
    expect($otherIp->tooManyAttempts())->toBeFalse();
});

it('is case- and whitespace-insensitive on the identifier', function () {
    $a = LoginThrottle::for('admin', 'User@Dots.test', '127.0.0.1');
    for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
        $a->hit();
    }

    $b = LoginThrottle::for('admin', '  user@dots.test ', '127.0.0.1');
    expect($b->tooManyAttempts())->toBeTrue();
});
