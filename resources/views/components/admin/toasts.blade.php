{{-- Toast stack driven by the Alpine `toasts` store (resources/js/admin.js).
     Livewire raises them with $this->dispatch('toast', tone:…, title:…, message:…).
     A session-flashed `admin.toast` (set before a redirect) is pushed on load. --}}
@php($flashToast = session('admin.toast'))
<div class="d-toasts" aria-live="polite" aria-atomic="false"
     @if ($flashToast) x-init="$store.toasts.push(@js($flashToast))" @endif
>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div
            class="d-toast"
            :class="`d-toast--${t.tone}`"
            :role="t.tone === 'error' ? 'alert' : 'status'"
            x-transition
        >
            <span class="d-toast__ico">
                <span x-show="t.tone === 'success'"><x-icon name="check" size="16" stroke="2" /></span>
                <span x-show="t.tone === 'error'"><x-icon name="circle-alert" size="16" stroke="2" /></span>
                <span x-show="t.tone === 'warning'"><x-icon name="triangle-alert" size="16" stroke="2" /></span>
                <span x-show="t.tone === 'info'"><x-icon name="info" size="16" stroke="2" /></span>
            </span>
            <div style="min-width: 0;">
                <div class="d-toast__title" x-show="t.title" x-text="t.title"></div>
                <div class="d-toast__msg" x-show="t.message" x-text="t.message"></div>
            </div>
            <button type="button" class="d-toast__x" @click="$store.toasts.dismiss(t.id)" aria-label="{{ __('ui.dismiss') }}">
                <x-icon name="x" size="16" />
            </button>
        </div>
    </template>
</div>
