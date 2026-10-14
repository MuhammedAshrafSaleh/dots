<?php

namespace App\Livewire\Site;

use App\Enums\NewsletterSource;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NewsletterForm extends Component
{
    #[Locked]
    public string $source = 'blog';

    public string $email = '';

    /** Honeypot — invisible to humans; bots fill it. */
    public string $website = '';

    public bool $submitted = false;

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function subscribe(): void
    {
        if ($this->website !== '') {
            $this->submitted = true;

            return;
        }

        $key = 'newsletter.'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('ui.newsletter_too_many'),
            ]);
        }

        $this->validate();

        NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower(trim($this->email))],
            [
                'source' => NewsletterSource::from($this->source),
                'locale' => app()->getLocale(),
                'ip' => request()->ip(),
            ]
        );

        RateLimiter::hit($key, 600);

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.site.newsletter-form');
    }
}
