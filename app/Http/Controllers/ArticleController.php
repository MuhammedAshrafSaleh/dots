<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __invoke(string $locale, string $slug): View
    {
        $post = Post::published()
            ->with(['media', 'category', 'tags', 'products.media'])
            ->where('slug->'.$locale, $slug)
            ->firstOrFail();

        $related = Post::published()
            ->with(['media', 'category'])
            ->where('post_category_id', $post->post_category_id)
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('site.article', compact('post', 'related', 'locale'));
    }
}
