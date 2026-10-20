<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterSource;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request, string $locale): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/']]);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower(trim($request->email))],
            [
                'source' => NewsletterSource::Footer,
                'locale' => $locale,
                'ip' => $request->ip(),
            ]
        );

        if ($subscriber->wasRecentlyCreated) {
            return redirect()->back()->with('toast_success', __('ui.subscribed'));
        }

        return redirect()->back()->with('toast_info', __('ui.newsletter_already_subscribed'));
    }
}
