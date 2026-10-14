@php
    /** @var \App\Models\Page $page */
    /** @var \Illuminate\Support\Collection<int,\App\Models\Page> $navPages */

    $title = $page->getTranslation('title', $locale);
    $lede  = $page->getTranslation('lede', $locale);
    $body  = $page->getTranslation('body', $locale);

    $pageUrl = fn (\App\Models\Page $p): string =>
        route('page', [$locale, $p->getTranslation('slug', $locale)]);
@endphp
<x-layouts.app :title="$page->getTranslation('meta_title', $locale) ?: $title">

    {{-- ========== 1. PAGE HEAD ========== --}}
    <section class="d-sec d-sec--subtle">
        <div class="d-container" style="padding-block:32px 24px;">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'href' => route('home', $locale)],
                ['label' => __('ui.footer_help')],
                ['label' => $title],
            ]" />

            <div class="d-rv" style="display:grid;gap:12px;padding-top:24px;max-width:680px;">
                <h1 class="t-h1" style="margin:0;">{{ $title }}</h1>

                @if ($lede)
                    <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $lede }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ========== 2. BODY ========== --}}
    <div class="d-container" style="padding-block:48px;">

        {{-- Mobile chips nav (hidden on desktop via CSS) --}}
        <nav aria-label="{{ __('ui.help_pages') }}" class="d-content-chips">
            @foreach ($navPages as $p)
                @php $isCurrent = $p->id === $page->id; @endphp
                <a href="{{ $pageUrl($p) }}"
                   class="d-chip{{ $isCurrent ? ' d-chip--active' : '' }}"
                   @if ($isCurrent) aria-current="page" @endif>
                    {{ $p->getTranslation('title', $locale) }}
                </a>
            @endforeach
        </nav>

        {{-- Three-column grid --}}
        <div class="d-content-layout" style="display:grid;grid-template-columns:200px minmax(0,1fr) 300px;gap:48px;align-items:start;">

            {{-- LEFT: sticky side nav (hidden on mobile via CSS) --}}
            <nav aria-label="{{ __('ui.help_pages') }}" class="d-content-sidenav"
                 style="position:sticky;top:24px;display:flex;flex-direction:column;gap:2px;">
                @foreach ($navPages as $p)
                    @php $isCurrent = $p->id === $page->id; @endphp
                    <a href="{{ $pageUrl($p) }}"
                       class="d-nav__mi{{ $isCurrent ? ' d-mega__cat--on' : '' }}"
                       style="font:700 14px var(--font-body);padding:10px 12px;text-decoration:none;"
                       @if ($isCurrent) aria-current="page" @endif>
                        {{ $p->getTranslation('title', $locale) }}
                    </a>
                @endforeach

                <a href="{{ route('track', $locale) }}"
                   class="d-nav__mi"
                   style="font:700 14px var(--font-body);padding:10px 12px;margin-top:8px;border-top:1px solid var(--border-1);border-radius:0;text-decoration:none;display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                    </svg>
                    {{ __('ui.track_order') }}
                </a>
            </nav>

            {{-- CENTRE: article --}}
            <article class="d-prose">
                {!! $body !!}
            </article>

            {{-- RIGHT: WhatsApp help box --}}
            <aside class="d-content-aside"
                   style="position:sticky;top:24px;border:1px solid var(--border-1);border-radius:var(--radius-lg);padding:24px;display:grid;gap:14px;background:var(--white);">
                <span style="width:44px;height:44px;border-radius:50%;background:var(--wa-100);color:var(--wa-600);display:grid;place-items:center;flex:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </span>

                <h3 style="margin:0;font:600 20px/1.3 var(--font-display);">
                    {{ __('ui.still_have_question') }}
                </h3>

                <p style="margin:0;font:500 14px/1.55 var(--font-body);color:var(--fg-2);">
                    {{ __('ui.team_answers_wa') }}
                </p>

                <x-whatsapp-button source="content_help" class="d-btn--block">
                    {{ __('ui.chat_on_whatsapp') }}
                </x-whatsapp-button>

                <span style="font:600 13px var(--font-body);color:var(--fg-3);display:inline-flex;gap:6px;align-items:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .948.684l1.498 4.493a1 1 0 0 1-.502 1.21l-2.257 1.13a11.042 11.042 0 0 0 5.516 5.516l1.13-2.257a1 1 0 0 1 1.21-.502l4.493 1.498a1 1 0 0 1 .684.949V19a2 2 0 0 1-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span dir="ltr">19 345</span>
                    &middot;
                    {{ __('ui.phone_days_hours') }}
                </span>
            </aside>
        </div>
    </div>

</x-layouts.app>
