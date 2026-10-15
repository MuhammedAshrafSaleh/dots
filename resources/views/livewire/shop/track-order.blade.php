@php
    use App\Enums\OrderStatus;
    use App\Enums\OrderPaymentStatus;

    /** @var \App\Models\Order|null $order */
    /** @var list<array{status:string,label:string,note:?string,time:?string,state:string}> $timeline */
    /** @var int $stepIndex */
    /** @var int $stepCount */
    /** @var string $locale */

    $payTone = $order ? match ($order->payment_status) {
        OrderPaymentStatus::Paid => ['success', __('ui.pay_status_paid')],
        OrderPaymentStatus::AwaitingTransfer => ['warning', __('ui.pay_status_awaiting')],
        OrderPaymentStatus::Failed => ['error', __('ui.pay_status_failed')],
        OrderPaymentStatus::Refunded => ['neutral', __('ui.pay_status_refunded')],
        default => ['neutral', __('ui.pay_status_pending')],
    } : null;

    $cancelled = $order && $order->status === OrderStatus::Cancelled;
    $progress = $stepCount > 1 ? (int) round(($stepIndex / ($stepCount - 1)) * 100) : 0;
@endphp

<div class="d-container" style="padding-block:32px 64px;display:grid;gap:24px;">

    {{-- Lookup form --}}
    <form wire:submit="track"
        style="border:1px solid var(--border-1);border-radius:var(--radius-lg);padding:24px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:16px;align-items:end;">
        <div style="display:grid;gap:6px;">
            <label for="track-number" style="font:700 13px var(--font-body);">{{ __('ui.order_number') }}</label>
            <input id="track-number" type="text" class="d-input" wire:model="number" dir="ltr"
                placeholder="DT-10001" autocomplete="off">
            @error('number') <span class="d-help d-help--error">{{ $message }}</span> @enderror
        </div>
        <div style="display:grid;gap:6px;">
            <label for="track-mobile" style="font:700 13px var(--font-body);">{{ __('ui.mobile_label') }}</label>
            <input id="track-mobile" type="tel" class="d-input" wire:model="mobile" dir="ltr"
                inputmode="tel" placeholder="01XXXXXXXXX" autocomplete="off">
            @error('mobile') <span class="d-help d-help--error">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="d-btn d-btn--dark" wire:loading.attr="disabled">
            <x-icon name="package-search" :size="18" />{{ __('ui.track_order') }}
        </button>
        <p class="d-help" style="grid-column:1 / -1;">{{ __('ui.track_lede') }}</p>
    </form>

    {{-- Empty / no-match state --}}
    @if ($searched && $order === null)
        <div role="alert"
            style="display:grid;grid-template-columns:auto minmax(0,1fr);gap:16px;padding:24px;border-radius:var(--radius-lg);background:var(--error-100);align-items:start;">
            <span style="width:40px;height:40px;border-radius:50%;background:var(--white);color:var(--error-600);display:grid;place-items:center;">
                <x-icon name="search-x" :size="20" />
            </span>
            <div style="display:grid;gap:8px;">
                <b style="font:800 16px/1.3 var(--font-body);">{{ __('ui.track_no_match_title') }}</b>
                <span style="font:500 14px/1.55 var(--font-body);color:var(--fg-2);">{{ __('ui.track_no_match_body') }}</span>
                <div>
                    <x-whatsapp-button source="order_help" class="d-btn--quote-outline d-btn--sm" :label="__('ui.track_ask_whatsapp')" />
                </div>
            </div>
        </div>
    @endif

    {{-- Result --}}
    @if ($order !== null)
        <div style="display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:32px;align-items:start;" class="d-track-grid">

            {{-- Left: status + timeline --}}
            <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:28px;display:grid;gap:22px;">
                <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
                    <div style="display:grid;gap:4px;">
                        <b style="font:500 20px/1 var(--font-mono);">{{ $order->number }}</b>
                        <span style="font:600 13px var(--font-body);color:var(--fg-3);">
                            {{ __('ui.track_placed') }} {{ $order->placed_at?->translatedFormat('j M Y · H:i') }}
                        </span>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @if ($cancelled)
                            <span class="d-badge d-badge--error">{{ __('ui.status_cancelled') }}</span>
                        @else
                            <span class="d-badge d-badge--dark">{{ __('ui.status_'.$order->status->value) }}</span>
                        @endif
                        <span class="d-badge d-badge--{{ $payTone[0] }}">{{ $payTone[1] }}</span>
                    </div>
                </div>

                @unless ($cancelled)
                    <div style="height:6px;border-radius:999px;background:var(--border-1);overflow:hidden;">
                        <div style="height:100%;width:{{ $progress }}%;background:var(--ink-900);border-radius:999px;transition:width .4s var(--ease-in-out);"></div>
                    </div>
                @endunless

                @if ($order->expected_delivery_at !== null && ! $cancelled)
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:var(--radius-md);background:var(--bg-subtle);font:600 14px var(--font-body);">
                        <x-icon name="truck" :size="18" style="color:var(--fg-3);" />
                        <span>{{ __('ui.expected_delivery') }}:
                            <b>{{ $order->expected_delivery_at->translatedFormat('l j M Y') }}</b></span>
                    </div>
                @endif

                {{-- Timeline --}}
                <ol style="list-style:none;margin:0;padding:0;">
                    @foreach ($timeline as $i => $step)
                        @php
                            $last = $loop->last;
                            $dot = match ($step['state']) {
                                'done' => 'background:var(--ink-900);border-color:var(--ink-900);',
                                'now' => 'background:var(--accent);border-color:var(--ink-900);',
                                'cancel' => 'background:var(--error-600);border-color:var(--error-600);',
                                default => 'background:var(--white);border-color:var(--border-control);',
                            };
                        @endphp
                        <li style="display:grid;grid-template-columns:20px minmax(0,1fr);gap:14px;">
                            <span style="position:relative;display:flex;justify-content:center;">
                                <i style="width:14px;height:14px;border-radius:50%;border:2px solid;margin-top:3px;z-index:1;{{ $dot }}"></i>
                                @unless ($last)
                                    <i style="position:absolute;top:18px;bottom:-3px;width:2px;background:{{ $step['state'] === 'done' ? 'var(--ink-900)' : 'var(--border-1)' }};"></i>
                                @endunless
                            </span>
                            <div style="display:grid;gap:3px;padding-bottom:{{ $last ? '0' : '22px' }};">
                                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                    <b style="font:800 15px/1.3 var(--font-body);color:{{ $step['state'] === 'todo' ? 'var(--fg-3)' : ($step['state'] === 'cancel' ? 'var(--error-600)' : 'var(--fg-1)') }};">{{ $step['label'] }}</b>
                                    <span style="font:600 13px/1.3 var(--font-body);color:var(--fg-3);font-variant-numeric:tabular-nums;">{{ $step['time'] ?? '—' }}</span>
                                </div>
                                @if ($step['state'] !== 'todo' && $step['note'])
                                    <span style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">{{ $step['note'] }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Right: items + total, help --}}
            <aside style="display:grid;gap:16px;">
                <div style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:20px;display:grid;gap:14px;">
                    <span style="font:700 12px var(--font-body);color:var(--fg-3);">
                        {{ __('ui.track_items') }} · {{ $order->items->sum('qty') }}
                    </span>
                    @foreach ($order->items as $item)
                        <div style="display:grid;grid-template-columns:44px minmax(0,1fr) auto;gap:12px;align-items:center;">
                            <span style="width:44px;height:44px;border-radius:6px;border:1px solid var(--border-1);background:var(--bg-subtle) center/cover no-repeat;@if ($item->image_url) background-image:url('{{ $item->image_url }}');@endif"></span>
                            <span style="font:700 13px/1.35 var(--font-body);min-width:0;">
                                {{ $item->product_name }}
                                <span style="display:block;font:500 12px var(--font-body);color:var(--fg-3);">
                                    @if ($item->variant_label){{ $item->variant_label }} · @endif×{{ $item->qty }}
                                </span>
                            </span>
                            <b style="font:800 13px var(--font-body);white-space:nowrap;">{{ number_format((float) $item->line_total, 0) }} {{ __('ui.egp') }}</b>
                        </div>
                    @endforeach
                    <div class="d-totals">
                        <div class="d-totals__grand"><span>{{ __('ui.order_total') }}</span><b style="font-size:18px;">{{ number_format((float) $order->total, 0) }} {{ __('ui.egp') }}</b></div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;align-items:center;padding:16px;border-radius:var(--radius-lg);background:var(--wa-100);flex-wrap:wrap;">
                    <span style="flex:1;min-width:160px;font:600 14px/1.4 var(--font-body);">{{ __('ui.track_change_prompt') }}</span>
                    <x-whatsapp-button source="order_help" class="d-btn--sm" :label="__('ui.whatsapp_us')" />
                </div>
            </aside>
        </div>
    @endif
</div>
