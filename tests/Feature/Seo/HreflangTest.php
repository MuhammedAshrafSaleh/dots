<?php

use App\Enums\PostStatus;
use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Support\Slug;
use Carbon\Carbon;

beforeEach(function () {
    $this->withoutVite();
});

// ─────────── helpers ───────────

function seoPost(): Post
{
    $cat = PostCategory::create([
        'name' => ['en' => 'SEO cat', 'ar' => 'تصنيف'],
        'slug' => Slug::fromTranslations(['en' => 'SEO cat', 'ar' => 'تصنيف']),
        'sort_order' => 0,
    ]);

    $title = ['en' => 'SEO Test Article', 'ar' => 'مقال اختبار'];

    return Post::create([
        'post_category_id' => $cat->id,
        'title' => $title,
        'slug' => Slug::fromTranslations($title),
        'excerpt' => ['en' => 'Excerpt.', 'ar' => 'مقتطف.'],
        'body' => ['en' => '<p>Body.</p>', 'ar' => '<p>محتوى.</p>'],
        'author_name' => ['en' => 'Author', 'ar' => 'مؤلف'],
        'author_role' => ['en' => 'Writer', 'ar' => 'كاتب'],
        'read_minutes' => 3,
        'is_featured' => false,
        'status' => PostStatus::Published,
        'published_at' => Carbon::parse('2026-01-01'),
    ]);
}

function seoService(): Service
{
    $title = ['en' => 'SEO Service', 'ar' => 'خدمة اختبار'];

    return Service::create([
        'number' => '01',
        'title' => $title,
        'slug' => Slug::fromTranslations($title),
        'excerpt' => ['en' => 'A service.', 'ar' => 'خدمة.'],
        'description' => ['en' => 'Full.', 'ar' => 'كامل.'],
        'included_title' => ['en' => '', 'ar' => ''],
        'duration_text' => ['en' => '', 'ar' => ''],
        'meta_title' => ['en' => '', 'ar' => ''],
        'meta_description' => ['en' => '', 'ar' => ''],
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

function seoProject(): Project
{
    $title = ['en' => 'SEO Project', 'ar' => 'مشروع اختبار'];

    return Project::create([
        'title' => $title,
        'slug' => Slug::fromTranslations($title),
        'type' => ProjectType::Residential,
        'status' => ProjectStatus::Completed,
        'stage' => ProjectStage::Handover,
        'scope_summary' => ['en' => 'Summary.', 'ar' => 'ملخص.'],
        'about' => ['en' => '', 'ar' => ''],
        'challenge' => ['en' => '', 'ar' => ''],
        'solution' => ['en' => '', 'ar' => ''],
        'result' => ['en' => '', 'ar' => ''],
        'meta_title' => ['en' => '', 'ar' => ''],
        'meta_description' => ['en' => '', 'ar' => ''],
        'location' => ['en' => '', 'ar' => ''],
        'client' => ['en' => '', 'ar' => ''],
        'is_active' => true,
        'completed_year' => 2025,
        'sort_order' => 0,
    ]);
}

function seoPage(): Page
{
    $title = ['en' => 'Delivery', 'ar' => 'التوصيل'];
    $slug = ['en' => 'delivery-seo-test', 'ar' => 'التوصيل-اختبار'];

    return Page::create([
        'key' => 'delivery-seo-test',
        'title' => $title,
        'slug' => $slug,
        'lede' => ['en' => 'Lede.', 'ar' => 'ليد.'],
        'body' => ['en' => '<p>Body.</p>', 'ar' => '<p>محتوى.</p>'],
        'meta_title' => ['en' => '', 'ar' => ''],
        'meta_description' => ['en' => '', 'ar' => ''],
        'is_active' => true,
        'show_in_footer' => true,
        'sort_order' => 0,
    ]);
}

// ─────────── list pages: hreflang on both locales ───────────

it('renders hreflang tags on home page', function () {
    $this->get('/en/')->assertOk()
        ->assertSee('hreflang="ar-EG"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="x-default"', false);

    $this->get('/ar/')->assertOk()
        ->assertSee('hreflang="ar-EG"', false)
        ->assertSee('hreflang="en"', false);
});

it('renders canonical link on home page', function () {
    $response = $this->get('/en/');
    $response->assertOk();
    $html = $response->getContent();
    expect($html)->toContain('rel="canonical"')
        ->toContain('href="'.url('/en').'"');
});

it('renders hreflang tags on brands list', function () {
    $this->get('/en/brands')->assertOk()
        ->assertSee('hreflang="ar-EG"', false)
        ->assertSee('hreflang="en"', false);
});

it('renders hreflang tags on blog list', function () {
    $this->get('/en/blog')->assertOk()
        ->assertSee('hreflang="ar-EG"', false)
        ->assertSee('hreflang="en"', false);
});

// ─────────── detail pages: per-locale slug alternates ───────────

it('renders correct hreflang alternates on article page', function () {
    $post = seoPost();
    $arSlug = $post->getTranslation('slug', 'ar');
    $enSlug = $post->getTranslation('slug', 'en');

    $html = $this->get("/en/blog/{$enSlug}")->assertOk()->getContent();

    expect($html)
        ->toContain(url("/ar/blog/{$arSlug}"))
        ->toContain(url("/en/blog/{$enSlug}"))
        ->toContain('hreflang="ar-EG"', false)
        ->toContain('hreflang="en"', false);
});

it('canonical on article is the locale URL without query string', function () {
    $post = seoPost();
    $enSlug = $post->getTranslation('slug', 'en');

    $html = $this->get("/en/blog/{$enSlug}")->assertOk()->getContent();

    expect($html)->toContain('rel="canonical"')
        ->toContain(url("/en/blog/{$enSlug}"));
});

it('renders correct hreflang alternates on service detail page', function () {
    $service = seoService();
    $arSlug = $service->getTranslation('slug', 'ar');
    $enSlug = $service->getTranslation('slug', 'en');

    $html = $this->get("/en/services/{$enSlug}")->assertOk()->getContent();

    expect($html)
        ->toContain(url("/ar/services/{$arSlug}"))
        ->toContain(url("/en/services/{$enSlug}"));
});

it('renders correct hreflang alternates on project detail page', function () {
    $project = seoProject();
    $arSlug = $project->getTranslation('slug', 'ar');
    $enSlug = $project->getTranslation('slug', 'en');

    $html = $this->get("/en/projects/{$enSlug}")->assertOk()->getContent();

    expect($html)
        ->toContain(url("/ar/projects/{$arSlug}"))
        ->toContain(url("/en/projects/{$enSlug}"));
});

it('renders correct hreflang alternates on content page', function () {
    $page = seoPage();
    $arSlug = $page->getTranslation('slug', 'ar');
    $enSlug = $page->getTranslation('slug', 'en');

    $html = $this->get("/en/p/{$enSlug}")->assertOk()->getContent();

    expect($html)
        ->toContain(url("/ar/p/{$arSlug}"))
        ->toContain(url("/en/p/{$enSlug}"));
});

// ─────────── OG tags ───────────

it('renders og:title and og:type on home', function () {
    $this->get('/en/')->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:type"', false)
        ->assertSee('content="website"', false);
});

it('renders og:type=article on article page', function () {
    $post = seoPost();
    $enSlug = $post->getTranslation('slug', 'en');

    $this->get("/en/blog/{$enSlug}")->assertOk()
        ->assertSee('content="article"', false);
});

// ─────────── sitemap ───────────

it('sitemap:generate command writes a valid XML file', function () {
    seoPost();
    seoProject();

    $this->artisan('sitemap:generate')->assertSuccessful();

    $path = public_path('sitemap.xml');
    expect(file_exists($path))->toBeTrue();

    $xml = simplexml_load_string(file_get_contents($path));
    expect($xml)->not->toBeFalse();
    expect((string) $xml->getName())->toBe('urlset');

    @unlink($path);
});

it('GET /sitemap.xml returns XML', function () {
    // Pre-generate so the route does not run the command in-test.
    $this->artisan('sitemap:generate');

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    @unlink(public_path('sitemap.xml'));
});

// ─────────── robots.txt ───────────

it('GET /robots.txt returns plain text with Sitemap directive', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap:');
});
