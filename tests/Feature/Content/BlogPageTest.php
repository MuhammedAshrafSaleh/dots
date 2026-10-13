<?php

use App\Enums\NewsletterSource;
use App\Enums\PostStatus;
use App\Livewire\Site\NewsletterForm;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\Slug;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    RateLimiter::clear('newsletter.127.0.0.1');
});

function makeCat(string $en, string $ar): PostCategory
{
    return PostCategory::create([
        'name' => ['en' => $en, 'ar' => $ar],
        'slug' => ['en' => Slug::make($en, 'en'), 'ar' => Slug::make($ar, 'ar')],
        'sort_order' => 0,
    ]);
}

function makePost(PostCategory $cat, array $attrs = []): Post
{
    $title = $attrs['title'] ?? ['en' => 'Test post', 'ar' => 'مقال تجريبي'];

    return Post::create(array_merge([
        'post_category_id' => $cat->id,
        'title' => $title,
        'slug' => Slug::fromTranslations($title),
        'excerpt' => ['en' => 'Excerpt.', 'ar' => 'مقتطف.'],
        'body' => ['en' => '<p>Body.</p>', 'ar' => '<p>المحتوى.</p>'],
        'author_name' => ['en' => 'Nour Hassan', 'ar' => 'نور حسن'],
        'author_role' => ['en' => 'Designer', 'ar' => 'مصممة'],
        'read_minutes' => 5,
        'is_featured' => false,
        'status' => PostStatus::Published,
        'published_at' => Carbon::parse('2026-09-01'),
    ], $attrs));
}

it('renders the blog list page in EN', function () {
    $cat = makeCat('Guides', 'أدلة');
    makePost($cat, ['title' => ['en' => 'Layered lighting guide', 'ar' => 'دليل الإضاءة']]);

    $this->get('/en/blog')->assertOk()->assertSee('Layered lighting guide');
});

it('renders the blog list page in AR', function () {
    $cat = makeCat('Smart home', 'المنزل الذكي');
    makePost($cat, ['title' => ['en' => 'Smart article', 'ar' => 'مقال ذكي']]);

    $this->get('/ar/blog')->assertOk()->assertSee('مقال ذكي');
});

it('shows the featured post as a wide card', function () {
    $cat = makeCat('Featured cat', 'تصنيف مميز');
    makePost($cat, [
        'title' => ['en' => 'The featured post', 'ar' => 'المقال المميز'],
        'is_featured' => true,
    ]);
    makePost($cat, ['title' => ['en' => 'Regular post', 'ar' => 'مقال عادي']]);

    $this->get('/en/blog')->assertOk()->assertSee('The featured post')->assertSee('Regular post');
});

it('does not include the featured post in the grid', function () {
    $cat = makeCat('Test cat', 'تصنيف');
    $featured = makePost($cat, [
        'title' => ['en' => 'Featured one', 'ar' => 'المميز'],
        'is_featured' => true,
    ]);

    // The grid (paginated) should not contain the featured post.
    $response = $this->get('/en/blog');
    $response->assertOk();

    // Only one instance of the title — in the wide card, not also in the grid.
    $this->assertStringContainsString('Featured one', $response->getContent());
});

it('filters posts by category via query string', function () {
    $guides = makeCat('Guides', 'أدلة');
    $tech = makeCat('Technical', 'تقني');
    makePost($guides, ['title' => ['en' => 'A guides post', 'ar' => 'مقال أدلة']]);
    makePost($tech, ['title' => ['en' => 'A tech post', 'ar' => 'مقال تقني']]);

    $res = $this->get('/en/blog?cat=guides');
    $res->assertOk()->assertSee('A guides post')->assertDontSee('A tech post');
});

it('filters posts by search query', function () {
    $cat = makeCat('General', 'عام');
    makePost($cat, ['title' => ['en' => 'Zigbee protocol post', 'ar' => 'مقال زيجبي']]);
    makePost($cat, ['title' => ['en' => 'Kitchen lighting post', 'ar' => 'مقال مطبخ']]);

    $res = $this->get('/en/blog?q=zigbee');
    $res->assertOk()->assertSee('Zigbee protocol post')->assertDontSee('Kitchen lighting post');
});

it('shows no-results message when nothing matches', function () {
    $cat = makeCat('Empty cat', 'تصنيف فارغ');
    makePost($cat, ['title' => ['en' => 'Some post', 'ar' => 'مقال']]);

    $this->get('/en/blog?q=xyznothing')
        ->assertOk()
        ->assertSee(__('ui.no_articles_filter'));
});

it('renders the article detail page', function () {
    $cat = makeCat('Guides', 'أدلة');
    $post = makePost($cat, ['title' => ['en' => 'My detailed post', 'ar' => 'مقال مفصل']]);

    $this->get('/en/blog/my-detailed-post')
        ->assertOk()
        ->assertSee('My detailed post')
        ->assertSee('Body.');
});

it('returns 404 for non-existent article slug', function () {
    $this->get('/en/blog/no-such-post')->assertNotFound();
});

it('renders the article in Arabic by locale slug', function () {
    $cat = makeCat('Guides', 'أدلة');
    $post = makePost($cat, ['title' => ['en' => 'English title', 'ar' => 'عنوان عربي']]);

    // Use route() so the slug is properly URL-encoded (Arabic chars → percent-encoded).
    $url = route('blog.show', ['locale' => 'ar', 'slug' => $post->getTranslation('slug', 'ar')]);
    $this->get($url)->assertOk()->assertSee('عنوان عربي');
});

it('shows related articles from the same category', function () {
    $cat = makeCat('Lighting', 'إضاءة');
    $other = makeCat('Other', 'أخرى');

    $post = makePost($cat, ['title' => ['en' => 'Main article', 'ar' => 'المقال الرئيسي']]);
    $related = makePost($cat, ['title' => ['en' => 'Related article', 'ar' => 'مقال ذو صلة']]);
    $unrelated = makePost($other, ['title' => ['en' => 'Unrelated article', 'ar' => 'مقال لا صلة له']]);

    $this->get('/en/blog/'.$post->getTranslation('slug', 'en'))
        ->assertOk()
        ->assertSee('Related article')
        ->assertDontSee('Unrelated article');
});

it('does not show draft posts on the blog list', function () {
    $cat = makeCat('Cat', 'تصنيف');
    makePost($cat, [
        'title' => ['en' => 'Draft post hidden', 'ar' => 'مسودة'],
        'status' => PostStatus::Draft,
    ]);

    $this->get('/en/blog')->assertOk()->assertDontSee('Draft post hidden');
});

it('does not show future-dated posts on the blog list', function () {
    $cat = makeCat('Cat', 'تصنيف');
    makePost($cat, [
        'title' => ['en' => 'Future post hidden', 'ar' => 'مستقبلي'],
        'published_at' => Carbon::now()->addDay(),
    ]);

    $this->get('/en/blog')->assertOk()->assertDontSee('Future post hidden');
});

// ──────────────────────── Newsletter ────────────────────────

it('subscribes a new email via the newsletter form', function () {
    Livewire::test(NewsletterForm::class, ['source' => 'blog'])
        ->set('email', 'reader@example.com')
        ->call('subscribe')
        ->assertSet('submitted', true);

    $row = NewsletterSubscriber::firstOrFail();
    expect($row->email)->toBe('reader@example.com')
        ->and($row->source)->toBe(NewsletterSource::Blog);
});

it('deduplicates newsletter subscriptions silently', function () {
    NewsletterSubscriber::create([
        'email' => 'existing@example.com',
        'source' => NewsletterSource::Footer->value,
        'locale' => 'en',
    ]);

    Livewire::test(NewsletterForm::class, ['source' => 'blog'])
        ->set('email', 'existing@example.com')
        ->call('subscribe')
        ->assertSet('submitted', true)
        ->assertHasNoErrors();

    // Still only one row — no duplicate.
    expect(NewsletterSubscriber::count())->toBe(1);
});

it('rejects an invalid email in the newsletter form', function () {
    Livewire::test(NewsletterForm::class, ['source' => 'blog'])
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertSet('submitted', false)
        ->assertHasErrors(['email']);
});

it('newsletter honeypot silently succeeds without saving', function () {
    Livewire::test(NewsletterForm::class, ['source' => 'blog'])
        ->set('email', 'bot@example.com')
        ->set('website', 'http://spam.com')
        ->call('subscribe')
        ->assertSet('submitted', true);

    expect(NewsletterSubscriber::count())->toBe(0);
});
