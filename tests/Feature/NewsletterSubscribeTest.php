<?php

use App\Enums\NewsletterSource;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->withoutVite();
    RateLimiter::clear('newsletter.127.0.0.1');
});

it('subscribes a new email and redirects back with a success flash', function () {
    $response = $this->post('/ar/newsletter/subscribe', ['email' => 'test@example.com']);

    $response->assertRedirect();
    $response->assertSessionHas('toast_success');
    expect(NewsletterSubscriber::where('email', 'test@example.com')->exists())->toBeTrue();
});

it('handles a duplicate email gracefully and returns an already-subscribed flash', function () {
    NewsletterSubscriber::create([
        'email' => 'dup@example.com',
        'source' => NewsletterSource::Footer,
        'locale' => 'ar',
    ]);

    $response = $this->post('/ar/newsletter/subscribe', ['email' => 'dup@example.com']);

    $response->assertRedirect();
    $response->assertSessionHas('toast_info');
    expect(NewsletterSubscriber::where('email', 'dup@example.com')->count())->toBe(1);
});

it('rejects an invalid email', function () {
    $response = $this->post('/ar/newsletter/subscribe', ['email' => 'not-an-email']);

    $response->assertSessionHasErrors('email');
    expect(NewsletterSubscriber::count())->toBe(0);
});

it('throttles after 10 attempts per minute', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/ar/newsletter/subscribe', ['email' => "user{$i}@example.com"]);
    }

    $response = $this->post('/ar/newsletter/subscribe', ['email' => 'overflow@example.com']);

    $response->assertStatus(429);
});
