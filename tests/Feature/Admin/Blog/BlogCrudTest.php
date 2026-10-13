<?php

use App\Enums\PostStatus;
use App\Livewire\Admin\Blog\Edit;
use App\Livewire\Admin\Blog\Index;
use App\Livewire\Admin\Blog\TaxIndex;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function blogTestCat(): PostCategory
{
    return PostCategory::where('slug->en', 'test-cat-blog')->first() ?? PostCategory::create([
        'name' => ['ar' => 'أدلة', 'en' => 'Guides'],
        'slug' => ['ar' => 'test-cat-blog-ar', 'en' => 'test-cat-blog'],
        'sort_order' => 1,
    ]);
}

function blogTestProduct(): Product
{
    $brand = Brand::where('slug', 'blog-test-brand')->first()
        ?? Brand::create(['name' => 'Blog Test Brand', 'slug' => 'blog-test-brand']);
    $cat = Category::where('slug_en', 'blog-test-pcat')->first()
        ?? Category::create(['level' => 3, 'name' => ['ar' => 'منتج', 'en' => 'Blog P Cat'], 'slug' => ['ar' => 'blog-test-pcat-ar', 'en' => 'blog-test-pcat']]);

    return Product::where('sku', 'BLOG-TEST-001')->first() ?? Product::create([
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'name' => ['ar' => 'منتج مدونة', 'en' => 'Blog Product'],
        'slug' => ['ar' => 'blog-product-ar', 'en' => 'blog-product'],
        'sku' => 'BLOG-TEST-001',
        'status' => 'active',
    ]);
}

it('gates the list behind the posts.view permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.blog'))->assertForbidden();

    loginAdmin(['posts.view']);
    $this->get(route('admin.blog'))->assertOk();
});

it('lists posts and filters by status tab', function () {
    $cat = blogTestCat();
    Post::create([
        'title' => ['ar' => 'مقال تجريبي مسودة', 'en' => 'Draft Post Test'],
        'slug' => ['ar' => 'draft-post-ar', 'en' => 'draft-post'],
        'post_category_id' => $cat->id,
        'status' => 'draft',
    ]);
    Post::create([
        'title' => ['ar' => 'مقال تجريبي منشور', 'en' => 'Published Post Test'],
        'slug' => ['ar' => 'published-post-ar', 'en' => 'published-post'],
        'post_category_id' => $cat->id,
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('مقال تجريبي مسودة')
        ->assertSee('مقال تجريبي منشور')
        ->set('statusTab', 'draft')
        ->assertSee('مقال تجريبي مسودة')
        ->assertDontSee('مقال تجريبي منشور')
        ->set('statusTab', 'published')
        ->assertDontSee('مقال تجريبي مسودة')
        ->assertSee('مقال تجريبي منشور');
});

it('searches posts by title', function () {
    $cat = blogTestCat();
    Post::create([
        'title' => ['ar' => 'الإضاءة العامة', 'en' => 'Ambient Light Guide'],
        'slug' => ['ar' => 'ambient-ar', 'en' => 'ambient-light-guide'],
        'post_category_id' => $cat->id,
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);
    Post::create([
        'title' => ['ar' => 'توفير الطاقة', 'en' => 'Energy Saving Tips'],
        'slug' => ['ar' => 'energy-ar', 'en' => 'energy-saving-tips'],
        'post_category_id' => $cat->id,
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Ambient')
        ->assertSee('الإضاءة العامة')
        ->assertDontSee('توفير الطاقة');
});

it('creates a post with required fields', function () {
    $cat = blogTestCat();

    Livewire::test(Edit::class)
        ->set('title.en', 'My First Post')
        ->set('title.ar', 'مقالي الأول')
        ->call('generateSlugs')
        ->assertSet('slug.en', 'my-first-post')
        ->set('status', 'draft')
        ->set('post_category_id', $cat->id)
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::where('slug->en', 'my-first-post')->firstOrFail();
    expect($post->getTranslation('title', 'en'))->toBe('My First Post')
        ->and($post->getTranslation('title', 'ar'))->toBe('مقالي الأول')
        ->and($post->status)->toBe(PostStatus::Draft);
});

it('renders category options with the real id as the option value', function () {
    $cat = blogTestCat();

    // Regression: PHP coerces numeric-string array keys back to int, so building
    // options as [id => label] made x-select use the label as the value, which then
    // failed `exists:post_categories,id` on save. Options must carry the real id.
    Livewire::test(Edit::class)
        ->assertSeeHtml('<option value="'.$cat->id.'"')
        ->assertDontSeeHtml('<option value="'.$cat->getTranslation('name', 'ar', false).'"');
});

it('requires both title locales', function () {
    $cat = blogTestCat();

    Livewire::test(Edit::class)
        ->set('title.en', 'Only EN')
        ->set('title.ar', '')
        ->set('slug.en', 'only-en')
        ->set('slug.ar', 'only-en-ar')
        ->set('post_category_id', $cat->id)
        ->call('save')
        ->assertHasErrors('title.ar');
});

it('requires category', function () {
    Livewire::test(Edit::class)
        ->set('title.en', 'No Cat')
        ->set('title.ar', 'بدون قسم')
        ->set('slug.en', 'no-cat')
        ->set('slug.ar', 'no-cat-ar')
        ->set('post_category_id', '')
        ->call('save')
        ->assertHasErrors('post_category_id');
});

it('requires publish_at when status is scheduled', function () {
    $cat = blogTestCat();

    Livewire::test(Edit::class)
        ->set('title.en', 'Scheduled Post')
        ->set('title.ar', 'مقال مجدول')
        ->set('slug.en', 'scheduled-no-date')
        ->set('slug.ar', 'scheduled-no-date-ar')
        ->set('post_category_id', $cat->id)
        ->set('status', 'scheduled')
        ->set('published_at', '')
        ->call('save')
        ->assertHasErrors('published_at');
});

it('scheduled post is hidden before its publish date', function () {
    $cat = blogTestCat();
    $post = Post::create([
        'title' => ['ar' => 'مقال مستقبلي', 'en' => 'Future Post'],
        'slug' => ['ar' => 'future-post-ar', 'en' => 'future-post'],
        'post_category_id' => $cat->id,
        'status' => 'scheduled',
        'published_at' => now()->addDays(3),
    ]);

    expect(Post::published()->where('id', $post->id)->exists())->toBeFalse();
});

it('scheduled post is visible after its publish date', function () {
    $cat = blogTestCat();
    $post = Post::create([
        'title' => ['ar' => 'مقال مضى وقته', 'en' => 'Past Scheduled Post'],
        'slug' => ['ar' => 'past-scheduled-ar', 'en' => 'past-scheduled'],
        'post_category_id' => $cat->id,
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    expect(Post::published()->where('id', $post->id)->exists())->toBeTrue();
});

it('updates an existing post', function () {
    $cat = blogTestCat();
    $post = Post::create([
        'title' => ['ar' => 'قديم', 'en' => 'Old Title'],
        'slug' => ['ar' => 'old-title-ar', 'en' => 'old-title'],
        'post_category_id' => $cat->id,
        'status' => 'draft',
    ]);

    Livewire::test(Edit::class, ['post' => $post])
        ->set('title.en', 'Updated Title')
        ->set('title.ar', 'عنوان محدّث')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->getTranslation('title', 'en'))->toBe('Updated Title')
        ->and($post->fresh()->status)->toBe(PostStatus::Published);
});

it('syncs tags and products to a post', function () {
    $cat = blogTestCat();
    $post = Post::create([
        'title' => ['ar' => 'مقال ربط', 'en' => 'Link Post'],
        'slug' => ['ar' => 'link-post-ar', 'en' => 'link-post'],
        'post_category_id' => $cat->id,
        'status' => 'draft',
    ]);

    $tag = Tag::where('slug', 'lighting-tips')->first() ?? Tag::create([
        'name' => ['ar' => 'نصائح الإضاءة', 'en' => 'Lighting Tips'],
        'slug' => 'lighting-tips',
    ]);

    $product = blogTestProduct();

    Livewire::test(Edit::class, ['post' => $post])
        ->call('toggleChip', 'selectedTagIds', $tag->id)
        ->call('addProduct', $product->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->tags()->count())->toBe(1)
        ->and($post->fresh()->products()->count())->toBe(1);
});

it('toggles featured from the list', function () {
    $cat = blogTestCat();
    $post = Post::create([
        'title' => ['ar' => 'مقال مميز', 'en' => 'Featured Post'],
        'slug' => ['ar' => 'featured-post-ar', 'en' => 'featured-post'],
        'post_category_id' => $cat->id,
        'status' => 'published',
        'published_at' => now()->subDay(),
        'is_featured' => false,
    ]);

    Livewire::test(Index::class)->call('toggleFeatured', $post->id);
    expect($post->fresh()->is_featured)->toBeTrue();
});

it('deletes a post from the list', function () {
    $cat = blogTestCat();
    $post = Post::create([
        'title' => ['ar' => 'مقال محذوف', 'en' => 'Delete Me Post'],
        'slug' => ['ar' => 'delete-me-post-ar', 'en' => 'delete-me-post'],
        'post_category_id' => $cat->id,
        'status' => 'draft',
    ]);

    Livewire::test(Index::class)->call('delete', $post->id);
    expect(Post::find($post->id))->toBeNull();
});

it('attaches cover image to the cover media collection', function () {
    Storage::fake('public');
    $cat = blogTestCat();

    Livewire::test(Edit::class)
        ->set('title.en', 'Cover Test Post')
        ->set('title.ar', 'اختبار الغلاف')
        ->set('slug.en', 'cover-test-post')
        ->set('slug.ar', 'cover-test-post-ar')
        ->set('post_category_id', $cat->id)
        ->set('imageUpload', UploadedFile::fake()->image('cover.jpg', 800, 450))
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::where('slug->en', 'cover-test-post')->firstOrFail();
    expect($post->getMedia('cover'))->toHaveCount(1);
});

it('creates and saves a category from TaxIndex', function () {
    Livewire::test(TaxIndex::class)
        ->call('addCategory')
        ->set('cats.0.name_en', 'Lighting Guides')
        ->set('cats.0.name_ar', 'أدلة الإضاءة')
        ->set('cats.0.slug_en', 'lighting-guides')
        ->set('cats.0.slug_ar', 'adilat-aliadaa')
        ->call('saveCategory', 0);

    expect(PostCategory::where('slug->en', 'lighting-guides')->exists())->toBeTrue();
});

it('creates and saves a tag from TaxIndex', function () {
    Livewire::test(TaxIndex::class)
        ->call('addTag')
        ->set('tags.0.name_en', 'LED')
        ->set('tags.0.name_ar', 'ليد')
        ->set('tags.0.slug', 'led')
        ->call('saveTag', 0);

    expect(Tag::where('slug', 'led')->exists())->toBeTrue();
});
