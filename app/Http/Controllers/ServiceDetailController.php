<?php

namespace App\Http\Controllers;

use App\Models\PageSection;
use App\Models\Service;
use Illuminate\View\View;

class ServiceDetailController extends Controller
{
    public function __invoke(string $locale, string $slug): View
    {
        $service = Service::active()
            ->with(['media', 'items', 'faqs'])
            ->where('slug_'.$locale, $slug)
            ->firstOrFail();

        $sections = PageSection::where('page', 'services')
            ->whereIn('key', ['process'])
            ->get()
            ->keyBy('key');

        return view('site.service-detail', compact('service', 'sections'));
    }
}
