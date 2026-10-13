<?php

namespace App\Http\Controllers;

use App\Enums\ProjectType;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectsCurrentController extends Controller
{
    public function __invoke(Request $request): View
    {
        $type = $request->get('type', 'all');

        $query = Project::active()->current()
            ->with('media')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order');

        if ($type !== 'all' && ProjectType::tryFrom($type)) {
            $query->where('type', $type);
        } else {
            $type = 'all';
        }

        $projects = $query->get();
        $lead = $type === 'all' ? ($projects->firstWhere('is_featured', true) ?? $projects->first()) : null;

        $types = ProjectType::cases();

        return view('site.projects-current', compact('projects', 'lead', 'type', 'types'));
    }
}
