@props([
    'title' => null,
])
{{-- Storefront auth shell (mirror of Account.jsx KAAuth): two-column white
     card — form slot beside a perks panel. Account screens are noindex. --}}
<x-layouts.app :title="$title">
    @push('head')
        <meta name="robots" content="noindex, nofollow">
    @endpush

    <section class="d-sec d-sec--subtle">
        <div class="d-container">
            <div class="d-auth">
                <div class="d-auth__form">
                    <div class="d-auth__form-inner">
                        {{ $slot }}
                    </div>
                </div>
                <aside class="d-auth__aside" aria-hidden="true">
                    <div class="d-auth__perks">
                        <x-eyebrow>{{ __('account.perks.eyebrow') }}</x-eyebrow>
                        <ul class="d-node-list">
                            <li>{{ __('account.perks.one') }}</li>
                            <li>{{ __('account.perks.two') }}</li>
                            <li>{{ __('account.perks.three') }}</li>
                        </ul>
                        <span class="d-auth__note">{{ __('account.perks.note') }}</span>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
