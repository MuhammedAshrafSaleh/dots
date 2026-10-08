<?php

use App\Enums\PostStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\Tag;

function makeLeafProduct(string $sku = 'P-1'): Product
{
    $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme-'.$sku]);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'Cat'],
        'slug' => ['ar' => 'ق-'.$sku, 'en' => 'cat-'.$sku],
    ]);

    return Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'م', 'en' => 'Prod'],
        'slug' => ['ar' => 'م-'.$sku, 'en' => 'prod-'.$sku],
        'sku' => $sku,
    ]);
}

it('relates a service to its items and casts translatable text', function () {
    $service = Service::create([
        'number' => '01',
        'title' => ['ar' => 'إضاءة', 'en' => 'Lighting'],
        'slug' => ['ar' => 'اضاءة', 'en' => 'lighting'],
    ]);
    $service->items()->create(['text' => ['ar' => 'بند', 'en' => 'Item one'], 'sort_order' => 1]);

    expect($service->items)->toHaveCount(1)
        ->and($service->items->first()->getTranslation('text', 'en'))->toBe('Item one')
        ->and($service->fresh()->slug_en)->toBe('lighting');
});

it('links projects to products and services and casts enums', function () {
    $project = Project::create([
        'status' => ProjectStatus::Current,
        'type' => ProjectType::Residential,
        'title' => ['ar' => 'فيلا', 'en' => 'Villa'],
        'slug' => ['ar' => 'فيلا', 'en' => 'villa'],
        'progress' => 40,
    ]);
    $service = Service::create([
        'number' => '02',
        'title' => ['ar' => 'ت', 'en' => 'Svc'],
        'slug' => ['ar' => 'ت', 'en' => 'svc'],
    ]);
    $product = makeLeafProduct('PP-1');

    $project->products()->attach($product);
    $project->services()->attach($service);

    expect($project->status)->toBe(ProjectStatus::Current)
        ->and($project->type)->toBe(ProjectType::Residential)
        ->and($project->products->first()->is($product))->toBeTrue()
        ->and($project->services->first()->is($service))->toBeTrue()
        ->and(Project::current()->count())->toBe(1);
});

it('relates posts to category, tags and products with a published scope', function () {
    $category = PostCategory::create(['name' => ['ar' => 'ن', 'en' => 'News'], 'slug' => ['ar' => 'ن', 'en' => 'news']]);
    $post = Post::create([
        'post_category_id' => $category->id,
        'title' => ['ar' => 'مقال', 'en' => 'Article'],
        'slug' => ['ar' => 'مقال', 'en' => 'article'],
        'status' => PostStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    $draft = Post::create([
        'post_category_id' => $category->id,
        'title' => ['ar' => 'مسودة', 'en' => 'Draft'],
        'slug' => ['ar' => 'مسودة', 'en' => 'draft'],
        'status' => PostStatus::Draft,
    ]);
    $tag = Tag::create(['name' => ['ar' => 'وسم', 'en' => 'Tag'], 'slug' => 'tag']);
    $product = makeLeafProduct('AP-1');

    $post->tags()->attach($tag);
    $post->products()->attach($product, ['sort_order' => 1]);

    expect($post->category->is($category))->toBeTrue()
        ->and($post->tags->first()->is($tag))->toBeTrue()
        ->and($post->products->first()->is($product))->toBeTrue()
        ->and($tag->posts->first()->is($post))->toBeTrue()
        ->and(Post::published()->pluck('id')->all())->toBe([$post->id])
        ->and($category->posts)->toHaveCount(2)
        ->and($draft->status)->toBe(PostStatus::Draft);
});

it('attaches polymorphic faqs to a category and a service, and keeps general ones', function () {
    $category = Category::create(['level' => 1, 'name' => ['ar' => 'ر', 'en' => 'Root'], 'slug' => ['ar' => 'ر', 'en' => 'root']]);
    $service = Service::create(['number' => '03', 'title' => ['ar' => 'خ', 'en' => 'S'], 'slug' => ['ar' => 'خ', 'en' => 's']]);

    $category->faqs()->create(['question' => ['ar' => 'س', 'en' => 'Q1'], 'answer' => ['ar' => 'ج', 'en' => 'A1']]);
    $service->faqs()->create(['question' => ['ar' => 'س', 'en' => 'Q2'], 'answer' => ['ar' => 'ج', 'en' => 'A2']]);
    Faq::create(['question' => ['ar' => 'عام', 'en' => 'General'], 'answer' => ['ar' => 'ج', 'en' => 'A']]); // faqable null

    expect($category->faqs->first()->getTranslation('question', 'en'))->toBe('Q1')
        ->and($service->faqs->first()->getTranslation('question', 'en'))->toBe('Q2')
        ->and(Faq::whereNull('faqable_type')->count())->toBe(1);
});

it('resolves a page by locale slug', function () {
    $page = Page::create([
        'key' => 'terms',
        'title' => ['ar' => 'الشروط', 'en' => 'Terms'],
        'slug' => ['ar' => 'الشروط', 'en' => 'terms'],
    ]);

    app()->setLocale('en');
    expect((new Page)->resolveRouteBinding('terms')?->is($page))->toBeTrue();
});
