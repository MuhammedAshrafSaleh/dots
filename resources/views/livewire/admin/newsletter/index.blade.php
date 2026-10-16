@php
    $langOptions = [['value' => 'ar', 'label' => 'العربية'], ['value' => 'en', 'label' => 'English']];
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.newsletter.title')" :sub="__('admin.newsletter.subtitle')">
        <x-slot:actions>
            @can('newsletter.export')
                <x-button variant="outline" icon="download" wire:click="export">{{ __('admin.newsletter.export') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div style="padding: 0 18px;">
            <x-tabs
                :value="$tab"
                :tabs="[
                    ['id' => 'all', 'label' => __('admin.newsletter.tab_all'), 'count' => $counts['all']],
                    ['id' => 'subscribed', 'label' => __('admin.newsletter.tab_subscribed'), 'count' => $counts['subscribed']],
                    ['id' => 'unsubscribed', 'label' => __('admin.newsletter.tab_unsubscribed'), 'count' => $counts['unsubscribed']],
                ]"
                x-on:tab-changed="$wire.set('tab', $event.detail)"
            />
        </div>

        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.newsletter.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.newsletter.search')" />
            <x-select size="sm" :aria-label="__('admin.newsletter.col_source')" :placeholder="__('admin.newsletter.all_sources')" wire:model.live="source" :options="$sources" />
            <x-select size="sm" :aria-label="__('admin.newsletter.col_language')" :placeholder="__('admin.newsletter.any_language')" wire:model.live="lang" :options="$langOptions" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.newsletter.col_email') }}</th>
                <th class="is-shrink">{{ __('admin.newsletter.col_language') }}</th>
                <th>{{ __('admin.newsletter.col_source') }}</th>
                <th>{{ __('admin.newsletter.col_date') }}</th>
                <th>{{ __('admin.newsletter.col_status') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $sub)
                @php $subscribed = $sub->unsubscribed_at === null; @endphp
                <tr wire:key="sub-{{ $sub->id }}">
                    <td><b dir="ltr" style="font-weight: 700;">{{ $sub->email }}</b></td>
                    <td class="is-shrink"><x-badge tone="outline">{{ $sub->locale === 'ar' ? 'العربية' : 'English' }}</x-badge></td>
                    <td>{{ __('admin.newsletter.source_'.$sub->source->value) }}</td>
                    <td class="a-mono" style="white-space: nowrap;">{{ $sub->created_at?->format('Y-m-d') }}</td>
                    <td>
                        <x-badge :tone="$subscribed ? 'success' : 'neutral'" :dot="$subscribed">
                            {{ $subscribed ? __('admin.newsletter.status_subscribed') : __('admin.newsletter.status_unsubscribed') }}
                        </x-badge>
                    </td>
                    <td class="is-shrink">
                        <x-admin.menu>
                            @if ($subscribed)
                                <button type="button" wire:click="unsubscribe({{ $sub->id }})">
                                    <x-icon name="mail-x" size="16" />{{ __('admin.newsletter.unsubscribe') }}
                                </button>
                            @else
                                <button type="button" wire:click="resubscribe({{ $sub->id }})">
                                    <x-icon name="mail-check" size="16" />{{ __('admin.newsletter.resubscribe') }}
                                </button>
                            @endif
                            <hr>
                            <button type="button" class="is-danger" wire:click="delete({{ $sub->id }})"
                                    wire:confirm="{{ __('admin.newsletter.delete_confirm') }}">
                                <x-icon name="trash-2" size="16" />{{ __('admin.newsletter.delete') }}
                            </button>
                        </x-admin.menu>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="mail" :title="__('admin.newsletter.empty_title')" :body="__('admin.newsletter.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>
