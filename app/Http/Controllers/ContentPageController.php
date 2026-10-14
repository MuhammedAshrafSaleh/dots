<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function __invoke(string $locale, string $page): View
    {
        $record = Page::active()
            ->where("slug_{$locale}", $page)
            ->first()
            ?? Page::active()->where('slug_'.($locale === 'ar' ? 'en' : 'ar'), $page)->firstOrFail();

        $navPages = Page::active()
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->get();

        $altLocale = $locale === 'ar' ? 'en' : 'ar';
        $altSlug = $record->getTranslation('slug', $altLocale);
        $alternateUrl = $altSlug ? "/{$altLocale}/p/{$altSlug}" : null;

        return view('site.content-page', [
            'page' => $record,
            'navPages' => $navPages,
            'locale' => $locale,
            'alternateUrl' => $alternateUrl,
        ]);
    }
}
