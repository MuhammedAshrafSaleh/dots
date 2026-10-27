<div>
    @if ($submitted)
        <div style="display:grid;gap:12px;padding:8px 0;">
            <span style="width:48px;height:48px;border-radius:50%;background:var(--green-100,#dcfce7);display:grid;place-items:center;">
                <x-icon name="check" :size="24" style="color:var(--green-600,#16a34a);" />
            </span>
            <h3 class="t-h3" style="margin:0;">{{ __('ui.subscribed') }}</h3>
            <p class="t-body" style="margin:0;color:var(--fg-2);">
                {{ $alreadySubscribed ? __('ui.newsletter_already_subscribed') : __('ui.newsletter_subscribed_lead') }}
            </p>
        </div>
    @else
        <form wire:submit="subscribe" novalidate style="display:flex;gap:8px;flex-wrap:wrap;">
            {{-- Honeypot: visually hidden, screen-reader hidden (RTL-safe clip, not off-screen left) --}}
            <div aria-hidden="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;">
                <label for="hp-nl-website">Website</label>
                <input id="hp-nl-website" type="text" wire:model="website" autocomplete="off" tabindex="-1" />
            </div>

            <div style="flex:1;min-width:200px;">
                <input
                    class="d-input{{ $errors->has('email') ? ' d-input--error' : '' }}"
                    type="email"
                    wire:model="email"
                    placeholder="{{ $placeholder ?: 'name@example.com' }}"
                    dir="ltr"
                    aria-label="{{ __('ui.email') }}"
                    style="height:52px;width:100%;"
                />
                @error('email')
                    <span class="d-field__error" style="display:block;margin-top:4px;">{{ $message }}</span>
                @enderror
            </div>

            <x-button type="submit" size="lg" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('ui.subscribe') }}</span>
                <span wire:loading>…</span>
            </x-button>
        </form>
    @endif
</div>
