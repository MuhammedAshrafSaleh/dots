@props([
    'items' => [],
    'multiple' => false,
    'defaultOpen' => [],
])
@php
    // Mirror of design/website/src/components/wayfinding/Accordion.jsx.
    // $items: list of ['id','title','content','disabled'?]. The + rotates to × when open.
@endphp
<div
    x-data="{
        open: @js(array_values($defaultOpen)),
        multiple: {{ $multiple ? 'true' : 'false' }},
        isOpen(id) { return this.open.includes(id); },
        toggle(id) { this.open = this.isOpen(id) ? this.open.filter(x => x !== id) : (this.multiple ? [...this.open, id] : [id]); },
    }"
    {{ $attributes->class(['d-acc']) }}
>
    @foreach ($items as $it)
        <div class="d-acc__item" :class="{ 'd-acc__item--open': isOpen(@js($it['id'])) }">
            <button type="button" class="d-acc__btn" :aria-expanded="isOpen(@js($it['id']))" @disabled($it['disabled'] ?? false) @click="toggle(@js($it['id']))">
                {{ $it['title'] }}<span class="d-acc__ico"><x-icon name="plus" size="16" stroke="2" /></span>
            </button>
            <div class="d-acc__panel" x-show="isOpen(@js($it['id']))" x-cloak>{{ $it['content'] }}</div>
        </div>
    @endforeach
</div>
