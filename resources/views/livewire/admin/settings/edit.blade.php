@php
    $tabs = ['contact', 'commerce', 'whatsapp', 'footer', 'seo'];
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.settings.title')" :sub="__('admin.settings.subtitle')">
        <x-slot:actions>
            @can('settings.update')
                <x-button icon="check" wire:click="save">{{ __('admin.settings.save') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    {{-- Tab bar --}}
    <div class="a-card" style="padding: 0 18px;">
        <div role="tablist" class="d-tabs">
            @foreach ($tabs as $t)
                <button role="tab" type="button" class="d-tab" aria-selected="{{ $activeTab === $t ? 'true' : 'false' }}"
                        wire:click="$set('activeTab', '{{ $t }}')">
                    {{ __('admin.settings.tab_'.$t) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Contact --}}
    <div @class(['a-hidden' => $activeTab !== 'contact'])>
        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.settings.card_contact')">
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
                    <x-input :label="__('admin.settings.whatsapp_number')" wire:model="whatsapp_number"
                        icon="message-circle" dir="ltr" :help="__('admin.settings.whatsapp_number_help')" />
                    <x-input :label="__('admin.settings.hotline')" wire:model="hotline" icon="phone" dir="ltr" />
                    <x-input :label="__('admin.settings.email')" wire:model="email" type="email" icon="mail" dir="ltr"
                        :error="$errors->first('email')" />
                    <x-input :label="__('admin.settings.maps_url')" wire:model="maps_url" icon="map-pin" dir="ltr"
                        :error="$errors->first('maps_url')" />
                </div>
                <x-admin.field-bilingual :label="__('admin.settings.showroom_label')" model="showroom_label" />
                <x-admin.field-bilingual :label="__('admin.settings.showroom_address')" model="showroom_address" />
            </x-admin.card>

            <x-admin.card :title="__('admin.settings.card_hours')">
                <x-admin.field-bilingual :label="__('admin.settings.working_hours')" model="working_hours" />
            </x-admin.card>

            <x-admin.card :title="__('admin.settings.card_social')">
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
                    <x-input label="Instagram" wire:model="social_instagram" icon="instagram" dir="ltr" />
                    <x-input label="Facebook" wire:model="social_facebook" icon="facebook" dir="ltr" />
                    <x-input label="LinkedIn" wire:model="social_linkedin" icon="linkedin" dir="ltr" />
                    <x-input label="YouTube" wire:model="social_youtube" icon="youtube" dir="ltr" />
                </div>
            </x-admin.card>
        </div>
    </div>

    {{-- Commerce --}}
    <div @class(['a-hidden' => $activeTab !== 'commerce'])>
        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.settings.card_tax')">
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
                    <x-input :label="__('admin.settings.vat_rate')" wire:model="vat_rate" type="number" dir="ltr"
                        :error="$errors->first('vat_rate')">
                        <x-slot:trailing><span style="font: 700 13px var(--font-body);">%</span></x-slot:trailing>
                    </x-input>
                    <x-input :label="__('admin.settings.low_stock_threshold')" wire:model="low_stock_threshold" type="number" dir="ltr"
                        :help="__('admin.settings.low_stock_threshold_help')" :error="$errors->first('low_stock_threshold')" />
                </div>
                <x-admin.field-bilingual :label="__('admin.settings.vat_note')" model="vat_note" />
                <x-admin.field-bilingual :label="__('admin.settings.project_pricing_note')" model="project_pricing_note" :multiline="true" :rows="2" />
            </x-admin.card>

            <x-admin.card :title="__('admin.settings.card_payments')">
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
                    <x-input :label="__('admin.settings.instapay_address')" wire:model="instapay_address" dir="ltr" />
                    <x-input :label="__('admin.settings.instapay_hold_hours')" wire:model="instapay_hold_hours" type="number" dir="ltr"
                        :error="$errors->first('instapay_hold_hours')">
                        <x-slot:trailing><span style="font: 700 13px var(--font-body);">{{ __('admin.settings.hours') }}</span></x-slot:trailing>
                    </x-input>
                    <x-input :label="__('admin.settings.order_prefix')" wire:model="order_prefix" dir="ltr"
                        :error="$errors->first('order_prefix')" />
                    <x-input :label="__('admin.settings.currency')" wire:model="currency" dir="ltr"
                        :error="$errors->first('currency')" />
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.settings.trust_items')">
                <x-slot:action><span class="a-count">{{ __('admin.settings.trust_items_sub') }}</span></x-slot:action>
                @foreach ($trust_items as $i => $item)
                    <div class="a-card" style="padding: 12px; display: grid; gap: 10px;" wire:key="trust-{{ $i }}">
                        <span class="a-count">#{{ $i + 1 }}</span>
                        <x-admin.icon-pick :label="__('admin.settings.trust_icon')" model="trust_items.{{ $i }}.icon" :value="$item['icon']" />
                        <x-admin.field-bilingual :label="__('admin.settings.trust_text')" model="trust_items.{{ $i }}" />
                    </div>
                @endforeach
            </x-admin.card>

            <x-admin.card :title="__('admin.settings.card_product_tabs')">
                <x-admin.field-bilingual :label="__('admin.settings.product_delivery_text')" model="product_delivery_text" :multiline="true" :rows="3" />
                <x-admin.field-bilingual :label="__('admin.settings.product_returns_text')" model="product_returns_text" :multiline="true" :rows="3" />
            </x-admin.card>
        </div>
    </div>

    {{-- WhatsApp templates --}}
    <div @class(['a-hidden' => $activeTab !== 'whatsapp'])>
        <div style="display: grid; gap: 16px;">
            @foreach ($templateVars as $key => $vars)
                <x-admin.card :title="__('admin.settings.wa_'.$key)">
                    @if (count($vars))
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center; padding-bottom: 4px;">
                            <span class="a-count">{{ __('admin.settings.wa_variables') }}</span>
                            @foreach ($vars as $var)
                                <code class="a-var" style="user-select: all;">{!! '{' . $var . '}' !!}</code>
                            @endforeach
                        </div>
                    @endif
                    <x-admin.field-bilingual :label="__('admin.settings.wa_template_text')" :model="$key" :multiline="true" :rows="3" />
                </x-admin.card>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <div @class(['a-hidden' => $activeTab !== 'footer'])>
        <x-admin.card :title="__('admin.settings.card_footer')">
            <x-slot:action><span class="a-count">{{ __('admin.settings.footer_hint') }}</span></x-slot:action>
            <x-admin.field-bilingual :label="__('admin.settings.footer_about')" model="footer_about_text" :multiline="true" :rows="3" />
            <x-admin.field-bilingual :label="__('admin.settings.footer_payments')" model="footer_payments_line" />
            <x-admin.field-bilingual :label="__('admin.settings.footer_copyright')" model="footer_copyright" />
        </x-admin.card>
    </div>

    {{-- SEO --}}
    <div @class(['a-hidden' => $activeTab !== 'seo'])>
        <x-admin.card :title="__('admin.settings.card_seo')">
            <x-slot:action><span class="a-count">{{ __('admin.settings.seo_hint') }}</span></x-slot:action>
            <x-admin.field-bilingual :label="__('admin.settings.seo_title')" model="seo_default_title" :counter="60" />
            <x-admin.field-bilingual :label="__('admin.settings.seo_description')" model="seo_default_description" :multiline="true" :rows="3" :counter="160" />
            <x-input :label="__('admin.settings.seo_og_image')" wire:model="seo_og_image" dir="ltr"
                :help="__('admin.settings.seo_og_help')" :error="$errors->first('seo_og_image')" />
        </x-admin.card>
    </div>
</div>
