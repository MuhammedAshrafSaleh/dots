<div class="a-page">
    <x-admin.head :title="__('admin.nav.page_content')" :sub="__('admin.page_sections.sub')">
        <x-slot:actions>
            <x-button variant="outline" icon="external-link"
                      href="{{ url('/'.app()->getLocale()) }}" target="_blank" rel="noopener">{{ __('admin.page_sections.preview') }}</x-button>
            <x-button icon="check" wire:click="save" wire:loading.attr="disabled">{{ __('admin.page_sections.save') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    {{-- Page tabs --}}
    <div class="a-card" style="padding: 0 18px;">
        <div class="d-tabs" role="tablist">
            @foreach ($pages as [$id, $label])
                <a role="tab"
                   class="d-tab {{ $page === $id ? 'is-active' : '' }}"
                   href="{{ route('admin.page-content', $id) }}"
                   wire:navigate>
                    {{ $label }}<span class="d-tab__n">{{ count(config("page_sections.{$id}", [])) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Section cards for the current page --}}
    <div style="display: grid; gap: 12px;">
        @foreach ($pageSchema as $sectionKey => $sectionFields)
            @php
                $sectionTitle = __("admin.page_sections.sections.{$page}.{$sectionKey}");
                $isOn = $form[$sectionKey]['is_active'] ?? true;
            @endphp
            <section class="a-card"
                     x-data="{ open: {{ $loop->first ? 'true' : 'false' }}, on: {{ $isOn ? 'true' : 'false' }} }"
                     :style="on ? '' : 'opacity: 0.7'">
                {{-- Section header --}}
                <header class="a-card__head"
                        style="cursor: pointer;"
                        :style="open ? '' : 'border-bottom: 0'"
                        @click="open = !open">
                    <span style="display: inline-flex; color: var(--ink-400); cursor: grab;" @click.stop>
                        <x-icon name="grip-vertical" size="16" />
                    </span>
                    <h3 style="flex: 1; min-width: 0; margin: 0;">
                        {{ $sectionTitle }}
                        <template x-if="!on">
                            <span style="margin-inline-start: 8px;">
                                <x-badge tone="outline">{{ __('admin.page_sections.hidden') }}</x-badge>
                            </span>
                        </template>
                    </h3>
                    <span @click.stop style="display: inline-flex; align-items: center; gap: 6px;">
                        <x-admin.switch
                            wire:model="form.{{ $sectionKey }}.is_active"
                            x-model="on"
                            @change="if (!$event.target.checked) open = false" />
                        <span class="a-count"
                              x-text="on ? '{{ __('admin.page_sections.active') }}' : '{{ __('admin.page_sections.off') }}'"></span>
                    </span>
                    <span style="margin-inline-start: 4px; display: inline-flex; transition: transform 0.15s;"
                          :style="open ? 'transform: rotate(180deg)' : ''">
                        <x-icon name="chevron-down" size="18" />
                    </span>
                </header>

                {{-- Section body --}}
                <div class="a-card__body" x-show="open" x-cloak>
                    <div style="display: grid; gap: 18px;">
                        @foreach ($sectionFields as $fieldName => $fieldSpec)
                            @php
                                $fType  = is_array($fieldSpec) ? ($fieldSpec['type'] ?? 'text') : $fieldSpec;
                                $fLabel = __("admin.page_sections.fields.{$fieldName}");
                            @endphp

                            @if ($fType === 'text')
                                <x-admin.field-bilingual
                                    :label="$fLabel"
                                    model="form.{{ $sectionKey }}.content.{{ $fieldName }}" />

                            @elseif ($fType === 'long_text')
                                <x-admin.rich
                                    :label="$fLabel"
                                    model="form.{{ $sectionKey }}.content.{{ $fieldName }}" />

                            @elseif ($fType === 'link')
                                <x-input
                                    dir="ltr"
                                    :label="$fLabel"
                                    icon="link"
                                    wire:model="form.{{ $sectionKey }}.content.{{ $fieldName }}" />

                            @elseif ($fType === 'icon')
                                <x-admin.icon-pick
                                    :label="$fLabel"
                                    model="form.{{ $sectionKey }}.content.{{ $fieldName }}"
                                    :value="$form[$sectionKey]['content'][$fieldName] ?? ''" />

                            @elseif ($fType === 'media')
                                @php $slot = "{$sectionKey}__{$fieldName}"; @endphp
                                <x-admin.image
                                    :label="$fLabel"
                                    :url="$mediaUrls[$slot] ?? null"
                                    model="uploads.{{ $slot }}"
                                    remove="removeMediaSlot('{{ $slot }}')" />

                            @elseif ($fType === 'video')
                                @php $slot = "{$sectionKey}__{$fieldName}"; @endphp
                                <x-admin.video
                                    :label="$fLabel"
                                    :url="$mediaUrls[$slot] ?? null"
                                    model="uploads.{{ $slot }}"
                                    remove="removeMediaSlot('{{ $slot }}')" />

                            @elseif ($fType === 'media_many')
                                @php $manyCount = $fieldSpec['count'] ?? 1; @endphp
                                <div class="d-field">
                                    <span class="d-label">{{ $fLabel }}</span>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                                        @for ($mi = 0; $mi < $manyCount; $mi++)
                                            @php $slot = "{$sectionKey}__{$fieldName}_{$mi}"; @endphp
                                            <x-admin.image
                                                :url="$mediaUrls[$slot] ?? null"
                                                model="uploads.{{ $slot }}"
                                                remove="removeMediaSlot('{{ $slot }}')" />
                                        @endfor
                                    </div>
                                </div>

                            @elseif ($fType === 'list')
                                @php
                                    $listCount  = $fieldSpec['count'] ?? 0;
                                    $listFields = $fieldSpec['fields'] ?? [];
                                @endphp
                                <div class="d-field">
                                    <span class="d-label">{{ $fLabel }}
                                        <span class="d-label__opt">· {{ $listCount }} {{ __('admin.page_sections.items') }}</span>
                                    </span>
                                    <div style="display: grid; gap: 10px;">
                                        @for ($li = 0; $li < $listCount; $li++)
                                            <div class="a-card" style="padding: 12px; display: grid; gap: 12px; background: var(--ink-50);">
                                                <span class="a-count">#{{ $li + 1 }}</span>
                                                @foreach ($listFields as $sf => $st)
                                                    @php
                                                        $sfLabel = __("admin.page_sections.fields.{$sf}");
                                                    @endphp
                                                    @if ($st === 'text')
                                                        <x-admin.field-bilingual
                                                            :label="$sfLabel"
                                                            model="form.{{ $sectionKey }}.content.{{ $fieldName }}.{{ $li }}.{{ $sf }}" />
                                                    @elseif ($st === 'long_text')
                                                        <x-admin.rich
                                                            :label="$sfLabel"
                                                            model="form.{{ $sectionKey }}.content.{{ $fieldName }}.{{ $li }}.{{ $sf }}" />
                                                    @elseif ($st === 'link')
                                                        <x-input
                                                            dir="ltr"
                                                            :label="$sfLabel"
                                                            icon="link"
                                                            wire:model="form.{{ $sectionKey }}.content.{{ $fieldName }}.{{ $li }}.{{ $sf }}" />
                                                    @elseif ($st === 'icon')
                                                        <x-admin.icon-pick
                                                            :label="$sfLabel"
                                                            model="form.{{ $sectionKey }}.content.{{ $fieldName }}.{{ $li }}.{{ $sf }}"
                                                            :value="$form[$sectionKey]['content'][$fieldName][$li][$sf] ?? ''" />
                                                    @elseif ($st === 'media')
                                                        @php $slot = "{$sectionKey}__item_{$sf}_{$li}"; @endphp
                                                        <x-admin.image
                                                            :label="$sfLabel"
                                                            :url="$mediaUrls[$slot] ?? null"
                                                            model="uploads.{{ $slot }}"
                                                            remove="removeMediaSlot('{{ $slot }}')" />
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>
