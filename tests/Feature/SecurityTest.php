<?php

use App\Http\Middleware\SecurityHeaders;
use App\Livewire\Account\Auth\Register;
use App\Livewire\Account\Auth\ResetPassword;
use App\Livewire\Shop\TrackOrder;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->withoutVite();
    foreach (['register', 'reset-password', 'track-order', 'checkout'] as $bucket) {
        RateLimiter::clear($bucket.'.127.0.0.1');
    }
});

it('adds baseline security headers to web responses', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    $response->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
    expect($response->headers->get('Permissions-Policy'))->toContain('geolocation=()');
});

it('sends HSTS only over HTTPS', function () {
    $middleware = new SecurityHeaders;
    $next = fn () => new Response('ok');

    $http = $middleware->handle(Request::create('http://dots.test/ar'), $next);
    expect($http->headers->has('Strict-Transport-Security'))->toBeFalse();

    $https = $middleware->handle(Request::create('https://dots.test/ar'), $next);
    expect($https->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains');
});

it('rate-limits registration after 5 attempts per IP', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('register.127.0.0.1', 600);
    }

    Livewire::test(Register::class)
        ->set('name', 'Ahmed Ali')
        ->set('mobile', '01012345678')
        ->set('email', 'ahmed@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->set('terms', true)
        ->call('register')
        ->assertHasErrors(['email']);

    expect(Customer::count())->toBe(0);
});

it('rate-limits the public order tracking lookup after 10 attempts per IP', function () {
    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit('track-order.127.0.0.1', 60);
    }

    Livewire::test(TrackOrder::class)
        ->set('number', 'DT-10001')
        ->set('mobile', '01000000000')
        ->call('track')
        ->assertHasErrors(['number'])
        ->assertSet('searched', false);
});

it('rate-limits password reset submissions after 5 attempts per IP', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('reset-password.127.0.0.1', 600);
    }

    Livewire::test(ResetPassword::class, ['token' => 'any-token'])
        ->set('email', 'someone@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->call('save')
        ->assertHasErrors(['email']);
});
