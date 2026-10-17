<div class="d-co__sec" id="k-book"
     x-on:consultation-submitted.window="$el.scrollIntoView({behavior:'smooth',block:'start'})">
    @if ($submitted)
        <div style="display:grid;gap:12px;padding:8px 0;">
            <span style="width:48px;height:48px;border-radius:50%;background:var(--green-100,#dcfce7);display:grid;place-items:center;">
                <x-icon name="check" :size="24" style="color:var(--green-600,#16a34a);" />
            </span>
            <h3 class="t-h3" style="margin:0;">{{ __('ui.request_received') }}</h3>
            <p class="t-body" style="margin:0;color:var(--fg-2);">{{ __('ui.request_received_lead') }}</p>
        </div>
    @else
        <div class="d-co__head">
            <span class="d-co__num"><x-icon name="calendar-days" :size="16" /></span>
            <h3>{{ __('ui.book_consultation') }}</h3>
        </div>
        <form wire:submit="submit" novalidate>
            {{-- Honeypot: visually hidden, screen-reader hidden --}}
            <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
                <label for="hp-website">Website</label>
                <input id="hp-website" type="text" wire:model="website" autocomplete="off" tabindex="-1" />
            </div>

            <div class="d-co__grid">
                <x-input
                    :label="__('ui.full_name')"
                    wire:model="name"
                    :error="$errors->first('name')"
                    autocomplete="name"
                />
                <x-input
                    :label="__('ui.mobile_label')"
                    wire:model="mobile"
                    type="tel"
                    dir="ltr"
                    placeholder="01X XXXX XXXX"
                    :error="$errors->first('mobile')"
                    autocomplete="tel"
                />
                <x-select
                    :label="__('ui.project_type_label')"
                    wire:model="projectType"
                    :options="$projectTypeOptions"
                    :placeholder="__('ui.select_placeholder')"
                    :error="$errors->first('projectType')"
                />
                <x-select
                    :label="__('ui.area_label')"
                    wire:model="areaRange"
                    :options="$areaRangeOptions"
                    :placeholder="__('ui.select_placeholder')"
                    :error="$errors->first('areaRange')"
                />
                <x-input
                    class="span-2"
                    :multiline="true"
                    :label="__('ui.notes_label_service')"
                    wire:model="notes"
                    :optional="true"
                    :placeholder="__('ui.notes_placeholder_service')"
                    :error="$errors->first('notes')"
                    rows="3"
                />
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;">
                <x-button type="submit" size="lg" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('ui.send_request') }}</span>
                    <span wire:loading>…</span>
                </x-button>
                <x-whatsapp-button source="consultation" :label="__('ui.or_whatsapp')" />
            </div>
        </form>
    @endif
</div>
