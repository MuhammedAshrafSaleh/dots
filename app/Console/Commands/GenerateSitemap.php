<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate public/sitemap.xml for all public pages in both locales';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        // Static pages — same slug in both locales, just swap the prefix.
        $statics = [
            ['ar' => '/ar',                 'en' => '/en'],
            ['ar' => '/ar/about',           'en' => '/en/about'],
            ['ar' => '/ar/brands',          'en' => '/en/brands'],
            ['ar' => '/ar/services',        'en' => '/en/services'],
            ['ar' => '/ar/projects',        'en' => '/en/projects'],
            ['ar' => '/ar/projects/previous', 'en' => '/en/projects/previous'],
            ['ar' => '/ar/blog',            'en' => '/en/blog'],
        ];

        foreach ($statics as $paths) {
            $sitemap->add(
                Url::create(url($paths['ar']))
                    ->addAlternate(url($paths['ar']), 'ar-EG')
                    ->addAlternate(url($paths['en']), 'en')
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }

        // Content pages (show_in_footer = navigation-relevant)
        Page::active()->get()->each(function (Page $page) use ($sitemap): void {
            $arSlug = $page->getTranslation('slug', 'ar');
            $enSlug = $page->getTranslation('slug', 'en');
            if (! $arSlug && ! $enSlug) {
                return;
            }
            $arUrl = $arSlug ? url("/ar/p/{$arSlug}") : url("/en/p/{$enSlug}");
            $enUrl = $enSlug ? url("/en/p/{$enSlug}") : url("/ar/p/{$arSlug}");

            $sitemap->add(
                Url::create($arUrl)
                    ->addAlternate($arUrl, 'ar-EG')
                    ->addAlternate($enUrl, 'en')
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.5)
            );
        });

        // Brands
        Brand::active()->get()->each(function (Brand $brand) use ($sitemap): void {
            $arUrl = url("/ar/brands/{$brand->slug}");
            $enUrl = url("/en/brands/{$brand->slug}");

            $sitemap->add(
                Url::create($arUrl)
                    ->addAlternate($arUrl, 'ar-EG')
                    ->addAlternate($enUrl, 'en')
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7)
            );
        });

        // Services
        Service::active()->get()->each(function (Service $service) use ($sitemap): void {
            $arSlug = $service->getTranslation('slug', 'ar');
            $enSlug = $service->getTranslation('slug', 'en');
            if (! $arSlug && ! $enSlug) {
                return;
            }
            $arUrl = url("/ar/services/{$arSlug}");
            $enUrl = url("/en/services/{$enSlug}");

            $sitemap->add(
                Url::create($arUrl)
                    ->addAlternate($arUrl, 'ar-EG')
                    ->addAlternate($enUrl, 'en')
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7)
            );
        });

        // Projects
        Project::active()->get()->each(function (Project $project) use ($sitemap): void {
            $arSlug = $project->getTranslation('slug', 'ar');
            $enSlug = $project->getTranslation('slug', 'en');
            if (! $arSlug && ! $enSlug) {
                return;
            }
            $arUrl = url("/ar/projects/{$arSlug}");
            $enUrl = url("/en/projects/{$enSlug}");

            $sitemap->add(
                Url::create($arUrl)
                    ->addAlternate($arUrl, 'ar-EG')
                    ->addAlternate($enUrl, 'en')
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7)
            );
        });

        // Blog posts
        Post::published()->get()->each(function (Post $post) use ($sitemap): void {
            $arSlug = $post->getTranslation('slug', 'ar');
            $enSlug = $post->getTranslation('slug', 'en');
            if (! $arSlug && ! $enSlug) {
                return;
            }
            $arUrl = url("/ar/blog/{$arSlug}");
            $enUrl = url("/en/blog/{$enSlug}");

            $sitemap->add(
                Url::create($arUrl)
                    ->addAlternate($arUrl, 'ar-EG')
                    ->addAlternate($enUrl, 'en')
                    ->setLastModificationDate($post->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.6)
            );
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('sitemap.xml written to public/.');

        return self::SUCCESS;
    }
}
