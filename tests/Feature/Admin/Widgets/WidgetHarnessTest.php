<?php

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Livewire\Admin\Concerns\WithAdminTable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Livewire;

/**
 * A throwaway Livewire component that mounts the shared admin widgets and the
 * form/table traits, so we can prove they compile and behave in a Livewire
 * context (the widgets are otherwise exercised through the catalog screens).
 */
class WidgetHarness extends Component
{
    use InteractsWithAdminForms;
    use WithAdminTable;

    /** @var array<string, string> */
    public array $name = ['en' => '', 'ar' => ''];

    /** @var array<string, string> */
    public array $slug = ['en' => '', 'ar' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['en' => '', 'ar' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['en' => '', 'ar' => ''];

    /** @var array<string, string> */
    public array $body = ['en' => '', 'ar' => ''];

    /** @var array<int, int|string> */
    public array $tags = [];

    public bool $flag = false;

    public string $icon = 'truck';

    public mixed $logo = null;

    public mixed $catalogue = null;

    /** @var array<int, mixed> */
    public array $photos = [];

    public function gen(): void
    {
        $this->fillSlugsFrom('name', 'slug');
    }

    public function render(): View
    {
        return view('tests::widget-harness');
    }
}

beforeEach(function () {
    // Register the harness view on the fly.
    app('view')->addNamespace('tests', __DIR__);
});

it('renders the shared admin widgets without error', function () {
    Livewire::test(WidgetHarness::class)
        ->assertOk()
        ->assertSee('EN');
});

it('toggles a chip value in both directions', function () {
    Livewire::test(WidgetHarness::class)
        ->call('toggleChip', 'tags', 'lighting')
        ->assertSet('tags', ['lighting'])
        ->call('toggleChip', 'tags', 'smart')
        ->assertSet('tags', ['lighting', 'smart'])
        ->call('toggleChip', 'tags', 'lighting')
        ->assertSet('tags', ['smart']);
});

it('auto-generates per-locale slugs from a source field without clobbering edits', function () {
    Livewire::test(WidgetHarness::class)
        ->set('name.en', 'Pendant Lamp')
        ->set('name.ar', 'مصباح معلق')
        ->set('slug.en', 'kept-slug') // manual edit must survive
        ->call('gen')
        ->assertSet('slug.en', 'kept-slug')
        ->assertSet('slug.ar', 'مصباح-معلق');
});

it('toggles sort direction and column', function () {
    Livewire::test(WidgetHarness::class)
        ->call('sortBy', 'name')
        ->assertSet('sortCol', 'name')
        ->assertSet('sortDir', 'asc')
        ->call('sortBy', 'name')
        ->assertSet('sortDir', 'desc')
        ->call('sortBy', 'sku')
        ->assertSet('sortCol', 'sku')
        ->assertSet('sortDir', 'asc');
});

it('toggles select-all for a page of ids', function () {
    Livewire::test(WidgetHarness::class)
        ->call('toggleSelectAll', [1, 2, 3])
        ->assertSet('selected', [1, 2, 3])
        ->call('toggleSelectAll', [1, 2, 3])
        ->assertSet('selected', []);
});
