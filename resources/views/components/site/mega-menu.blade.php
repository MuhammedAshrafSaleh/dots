@php
    // Category mega-menu: root rail → group columns → promo card. Data from the
    // category tree (Navigation) + global.mega_promo section. Mirror of MegaMenu.jsx.
    use App\Models\PageSection;
    use App\Services\Navigation;

    $roots = app(Navigation::class)->megaTree();
    $promo = PageSection::where('page', 'global')->where('key', 'mega_promo')->first();
    $promoImg = $promo?->getFirstMediaUrl('section') ?: null;
    $catUrl = fn ($cat) => route('category', ['category' => $cat->getTranslation('slug', $locale)]);
@endphp
@if ($roots->isNotEmpty())
    <div class="d-mega" role="region" aria-label="{{ __('ui.categories') }}" x-show="mega" x-cloak x-transition.opacity
        x-data="{ active: {{ $roots->first()->id }} }">
        <div class="d-container d-mega__in">
            <div class="d-mega__cats">
                @foreach ($roots as $root)
                    <a href="{{ $catUrl($root) }}" class="d-mega__cat" :class="{ 'd-mega__cat--on': active === {{ $root->id }} }"
                        x-on:mouseenter="active = {{ $root->id }}" x-on:focus="active = {{ $root->id }}">
                        <x-icon :name="$root->icon ?? 'layout-grid'" :size="20" />{{ $root->getTranslation('name', $locale) }}<x-icon name="chevron-right" :size="16" />
                    </a>
                @endforeach
            </div>
            @foreach ($roots as $root)
                <div style="display:flex;flex-direction:column;" x-show="active === {{ $root->id }}" x-cloak>
                    <div class="d-mega__cols">
                        @foreach ($root->children as $group)
                            <div class="d-mega__col">
                                <h4><span class="d-dot"></span>{{ $group->getTranslation('name', $locale) }}</h4>
                                <ul>
                                    @foreach ($group->children as $leaf)
                                        <li><a href="{{ $catUrl($leaf) }}">{{ $leaf->getTranslation('name', $locale) }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-mega__foot" style="margin-top:auto;">
                        <a href="{{ $catUrl($root) }}" style="font-weight:800;">{{ __('ui.view_all') }} {{ $root->getTranslation('name', $locale) }}</a>
                        <span>{{ __('ui.catalog_summary') }}</span>
                    </div>
                </div>
            @endforeach
            <div class="d-mega__promo">
                <x-image-frame ratio="4 / 3" radius="var(--radius-md)" :src="$promoImg" />
                @if ($promo)
                    <div style="font:600 18px/1.3 var(--font-display);">{{ $promo->field('title', $locale) }}</div>
                    <x-button variant="dark" size="sm" iconEnd="arrow-right" :href="url($locale.($promo->field('button_link') ?: '/services'))">{{ $promo->field('button_label', $locale) }}</x-button>
                @endif
            </div>
        </div>
    </div>
@endif
