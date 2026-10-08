<div class="a-page">
    <x-admin.head
        :title="__('admin.dashboard.welcome', ['name' => $name])"
        :sub="__('admin.dashboard.lede')"
    />

    <x-admin.card :title="__('admin.dashboard.quick')" :body="false">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1px; background: var(--border-1);">
            @foreach (config('admin_nav.groups') as $group)
                @foreach ($group['items'] as [$key, $icon, $labelKey, $routeName, $permission])
                    @continue($key === 'dashboard')
                    @if (\Illuminate\Support\Facades\Route::has($routeName) && auth('admin')->user()?->can($permission))
                        <a href="{{ route($routeName) }}"
                           style="display: flex; align-items: center; gap: 10px; padding: 16px 18px; background: var(--surface-1); color: var(--fg-1); text-decoration: none; font: 700 14px var(--font-body);">
                            <x-icon :name="$icon" size="18" />{{ __('admin.nav.'.$labelKey) }}
                        </a>
                    @endif
                @endforeach
            @endforeach
        </div>
    </x-admin.card>
</div>
