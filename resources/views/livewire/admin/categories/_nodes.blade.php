{{-- Recursive sibling group: one sortable container per level so drag
     reorders only within siblings. $nodes, $level in scope. --}}
<div x-data="sortable({ wire: $wire, method: 'reorder' })">
    @foreach ($nodes as $node)
        <div
            class="a-tree__row {{ $level > 1 ? 'is-l'.$level : '' }} {{ $selectedId === $node->id ? 'is-sel' : '' }}"
            data-sortable-key="{{ $node->id }}" draggable="true"
            @dragstart="start($event, {{ $loop->index }})" @dragover="over($event)" @drop="drop($event, {{ $loop->index }})"
            wire:key="cat-row-{{ $node->id }}"
            wire:click="select({{ $node->id }})"
        >
            <span class="a-grip"><x-icon name="grip-vertical" size="16" /></span>
            @if ($level < 3 && $node->children->isNotEmpty())
                <button type="button" class="a-tree__tog" wire:click.stop="toggle({{ $node->id }})" aria-expanded="{{ in_array($node->id, $expanded, true) ? 'true' : 'false' }}" aria-label="{{ __('admin.widgets.more_actions') }}">
                    <x-icon :name="in_array($node->id, $expanded, true) ? 'chevron-down' : 'chevron-right'" size="16" />
                </button>
            @else
                <span style="width: 24px;"></span>
            @endif
            @if ($level === 1)
                <span class="a-kpi__ico" style="width: 30px; height: 30px;"><x-icon :name="$node->icon ?: 'folder'" size="16" /></span>
            @endif
            <span style="flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $node->getTranslation('name', app()->getLocale(), false) }}</span>
            @unless ($node->is_active)<x-badge tone="outline">{{ __('admin.categories.inactive') }}</x-badge>@endunless
            <span class="a-count">{{ number_format($node->products_count ?? 0) }}</span>
            @if ($level < 3)
                @can('categories.create')
                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click.stop="addChild({{ $node->id }})" aria-label="{{ __('admin.categories.add_child') }}"><x-icon name="plus" size="16" /></button>
                @endcan
            @endif
        </div>

        @if ($level < 3 && in_array($node->id, $expanded, true) && $node->children->isNotEmpty())
            <div style="padding-inline-start: 22px;">
                @include('livewire.admin.categories._nodes', ['nodes' => $node->children, 'level' => $level + 1])
            </div>
        @endif
    @endforeach
</div>
