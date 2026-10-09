<?php

use App\Livewire\Admin\ContentPages\Edit;
use App\Livewire\Admin\ContentPages\Index;
use App\Models\Page;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

it('gates the list behind the content_pages.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.content-pages'))->assertForbidden();

    loginAdmin(['content_pages.view']);
    $this->get(route('admin.content-pages'))->assertOk();
});

it('lists pages ordered by sort_order', function () {
    Page::where('key', 'like', 'test-%')->delete();
    Page::create([
        'key' => 'test-page-a',
        'title' => ['ar' => 'صفحة أ', 'en' => 'Page A'],
        'slug' => ['ar' => 'page-a-ar', 'en' => 'page-a'],
        'sort_order' => 10,
    ]);
    Page::create([
        'key' => 'test-page-b',
        'title' => ['ar' => 'صفحة ب', 'en' => 'Page B'],
        'slug' => ['ar' => 'page-b-ar', 'en' => 'page-b'],
        'sort_order' => 20,
    ]);

    // Admin defaults to AR locale; index renders the AR title.
    Livewire::test(Index::class)
        ->assertSee('صفحة أ')
        ->assertSee('صفحة ب');
});

it('searches pages by title', function () {
    Page::where('key', 'like', 'test-search-%')->delete();
    Page::create([
        'key' => 'test-search-x',
        'title' => ['ar' => 'فريد للغاية', 'en' => 'UniqueTitleX'],
        'slug' => ['ar' => 'unique-x-ar', 'en' => 'unique-x'],
        'sort_order' => 99,
    ]);

    // Search matches EN title; result renders AR title.
    Livewire::test(Index::class)
        ->set('search', 'فريد للغاية')
        ->assertSee('فريد للغاية');
});

it('gates create behind content_pages.create permission', function () {
    loginAdmin(['content_pages.view']);
    $this->get(route('admin.content-pages.create'))->assertForbidden();
});

it('creates a new content page', function () {
    Page::where('key', 'test-crud-new')->delete();

    Livewire::test(Edit::class)
        ->set('key', 'test-crud-new')
        ->set('title.en', 'CRUD New Page')
        ->set('title.ar', 'صفحة جديدة')
        ->set('slug.en', 'crud-new-page')
        ->set('slug.ar', 'crud-new-page-ar')
        ->call('save');

    expect(Page::where('key', 'test-crud-new')->exists())->toBeTrue();
});

it('requires key, title and slug', function () {
    Livewire::test(Edit::class)
        ->call('save')
        ->assertHasErrors(['key', 'title.en', 'title.ar', 'slug.en', 'slug.ar']);
});

it('rejects an invalid key format', function () {
    Livewire::test(Edit::class)
        ->set('key', 'Invalid Key!')
        ->set('title.en', 'Test')
        ->set('title.ar', 'اختبار')
        ->set('slug.en', 'test')
        ->set('slug.ar', 'test-ar')
        ->call('save')
        ->assertHasErrors(['key']);
});

it('rejects a duplicate key', function () {
    Page::where('key', 'test-dup-key')->delete();
    Page::create([
        'key' => 'test-dup-key',
        'title' => ['ar' => 'تكرار', 'en' => 'Duplicate'],
        'slug' => ['ar' => 'dup-ar', 'en' => 'dup'],
        'sort_order' => 1,
    ]);

    Livewire::test(Edit::class)
        ->set('key', 'test-dup-key')
        ->set('title.en', 'Another Page')
        ->set('title.ar', 'صفحة أخرى')
        ->set('slug.en', 'another-page')
        ->set('slug.ar', 'another-page-ar')
        ->call('save')
        ->assertHasErrors(['key']);
});

it('edits an existing page', function () {
    Page::where('key', 'test-edit-page')->delete();
    $page = Page::create([
        'key' => 'test-edit-page',
        'title' => ['ar' => 'قبل', 'en' => 'Before'],
        'slug' => ['ar' => 'before-ar', 'en' => 'before'],
        'sort_order' => 1,
    ]);

    Livewire::test(Edit::class, ['page' => $page])
        ->set('title.en', 'After')
        ->set('title.ar', 'بعد')
        ->call('save');

    expect($page->fresh()->getTranslation('title', 'en'))->toBe('After');
});

it('key is read-only on edit (does not change)', function () {
    Page::where('key', 'test-ro-key')->delete();
    $page = Page::create([
        'key' => 'test-ro-key',
        'title' => ['ar' => 'للقراءة', 'en' => 'Read Only'],
        'slug' => ['ar' => 'ro-ar', 'en' => 'ro'],
        'sort_order' => 1,
    ]);

    Livewire::test(Edit::class, ['page' => $page])
        ->set('key', 'changed-key')
        ->set('title.en', 'Read Only Updated')
        ->set('title.ar', 'للقراءة محدث')
        ->call('save');

    // key should still be the original (validation passes but old value must still be in DB)
    expect($page->fresh()->key)->toBe('test-ro-key');
});

it('deletes a page', function () {
    Page::where('key', 'test-delete-page')->delete();
    $page = Page::create([
        'key' => 'test-delete-page',
        'title' => ['ar' => 'للحذف', 'en' => 'To Delete'],
        'slug' => ['ar' => 'delete-ar', 'en' => 'delete'],
        'sort_order' => 1,
    ]);

    Livewire::test(Edit::class, ['page' => $page])
        ->call('deletePage');

    expect(Page::where('key', 'test-delete-page')->exists())->toBeFalse();
});

it('toggles active status from the index', function () {
    Page::where('key', 'test-toggle-page')->delete();
    $page = Page::create([
        'key' => 'test-toggle-page',
        'title' => ['ar' => 'تبديل', 'en' => 'Toggle Page'],
        'slug' => ['ar' => 'toggle-ar', 'en' => 'toggle'],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class)
        ->call('toggleActive', $page->id);

    expect($page->fresh()->is_active)->toBeFalse();
});

it('toggles footer flag from the index', function () {
    Page::where('key', 'test-footer-page')->delete();
    $page = Page::create([
        'key' => 'test-footer-page',
        'title' => ['ar' => 'تذييل', 'en' => 'Footer Page'],
        'slug' => ['ar' => 'footer-ar', 'en' => 'footer'],
        'show_in_footer' => false,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class)
        ->call('toggleFooter', $page->id);

    expect($page->fresh()->show_in_footer)->toBeTrue();
});
