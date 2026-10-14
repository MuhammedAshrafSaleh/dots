@props([
    'scopes' => true,   // show the scope <select> (All / categories / Brands)
    'size' => null,     // 'sm' for the compact bar
    'iconOnly' => false, // submit button shows an arrow icon instead of the word
    'placeholder' => null,
])
@php
    // Catalog search → GET route('search') with `q` + `scope`. Mirror of SearchBar.jsx.
    // Scope options are DB-driven (mega root categories) plus All / Brands UI labels.
    use App\Services\Navigation;

    $roots = $scopes ? app(Navigation::class)->megaTree() : collect();
    $isz = $size === 'sm' ? 16 : 18;
    $classes = array_filter(['d-search', $size === 'sm' ? 'd-search--sm' : null]);
@endphp
<form role="search" method="GET" action="{{ route('search') }}" {{ $attributes->class($classes) }}>
    @if ($scopes)
        <div class="d-search__scope">
            <select name="scope" aria-label="{{ __('ui.search_in') }}">
                <option value="">{{ __('ui.scope_all') }}</option>
                @foreach ($roots as $root)
                    <option value="c:{{ $root->getTranslation('slug', $locale) }}">{{ $root->getTranslation('name', $locale) }}</option>
                @endforeach
                <option value="brands">{{ __('ui.scope_brands') }}</option>
            </select>
            <x-icon name="chevron-down" :size="14" />
        </div>
    @endif
    <label class="d-search__field">
        <x-icon name="search" :size="18" />
        <input type="search" name="q" value="{{ request('q') }}"
            placeholder="{{ $placeholder ?? __('ui.search_placeholder') }}"
            aria-label="{{ __('ui.search') }}" />
    </label>
    <button type="submit" class="d-search__btn" aria-label="{{ __('ui.search') }}">
        @if ($iconOnly)
            <x-icon name="arrow-right" :size="18" />
        @else
            {{ __('ui.search') }}
        @endif
    </button>
</form>
