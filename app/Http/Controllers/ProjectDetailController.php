<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\View\View;

class ProjectDetailController extends Controller
{
    public function __invoke(string $locale, string $slug): View
    {
        $project = Project::active()
            ->with(['media', 'products.media', 'services'])
            ->where("slug_{$locale}", $slug)
            ->firstOrFail();

        $isCurrent = $project->status === ProjectStatus::Current;

        // Related: same type + status first, then fall back to same status only.
        $related = Project::active()
            ->where('id', '!=', $project->id)
            ->where('status', $project->status->value)
            ->where('type', $project->type->value)
            ->with('media')
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $more = Project::active()
                ->where('id', '!=', $project->id)
                ->where('status', $project->status->value)
                ->whereNotIn('id', $related->pluck('id'))
                ->with('media')
                ->orderBy('sort_order')
                ->take(3 - $related->count())
                ->get();
            $related = $related->concat($more);
        }

        return view('site.project-detail', compact('project', 'isCurrent', 'related'));
    }
}
