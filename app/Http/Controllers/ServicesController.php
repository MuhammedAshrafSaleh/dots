<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\PageSection;
use App\Models\Service;
use Illuminate\View\View;

class ServicesController extends Controller
{
    public function __invoke(): View
    {
        $sections = PageSection::where('page', 'services')
            ->get()
            ->keyBy('key');

        $services = Service::active()
            ->with('media')
            ->orderBy('sort_order')
            ->get();

        $faqs = Faq::whereNull('faqable_type')
            ->orderBy('sort_order')
            ->get();

        return view('site.services', compact('sections', 'services', 'faqs'));
    }
}
