<?php

namespace App\Http\Controllers;

use App\Models\PageSection;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __invoke(string $locale): View
    {
        $q = request()->string('q')->trim()->toString();
        $cat = request()->string('cat')->trim()->toString();

        $sections = PageSection::where('page', 'blog')->get()->keyBy('key');
        $globalSections = PageSection::where('page', 'global')->whereIn('key', ['newsletter'])->get()->keyBy('key');

        $categories = PostCategory::orderBy('sort_order')->get();

        $featured = Post::published()->where('is_featured', true)->with(['media', 'category'])->first();

        $posts = Post::published()
            ->with(['media', 'category'])
            ->when($featured, fn ($query) => $query->where('id', '!=', $featured->id))
            ->when($cat && $cat !== 'all', fn ($query) => $query->whereHas(
                'category',
                fn ($cq) => $cq->where('slug->'.$locale, $cat)
            ))
            ->when($q, fn ($query) => $query->whereRaw(
                'LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, ?))) LIKE ?',
                ['$."'.$locale.'"', '%'.mb_strtolower($q).'%']
            ))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('site.blog', compact('sections', 'globalSections', 'categories', 'featured', 'posts', 'q', 'cat', 'locale'));
    }
}
