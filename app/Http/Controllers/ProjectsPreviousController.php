<?php

namespace App\Http\Controllers;

use App\Enums\ProjectType;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectsPreviousController extends Controller
{
    public function __invoke(Request $request): View
    {
        $type = $request->get('type', 'all');
        $year = $request->get('year', 'all');

        $query = Project::active()->completed()
            ->with('media')
            ->orderByDesc('completed_year')
            ->orderBy('sort_order');

        if ($type !== 'all' && ProjectType::tryFrom($type)) {
            $query->where('type', $type);
        } else {
            $type = 'all';
        }

        if ($year !== 'all' && is_numeric($year)) {
            $query->where('completed_year', (int) $year);
        } else {
            $year = 'all';
        }

        $projects = $query->paginate(8)->withQueryString();

        $years = Project::active()->completed()
            ->distinct()
            ->orderByDesc('completed_year')
            ->pluck('completed_year');

        $types = ProjectType::cases();

        return view('site.projects-previous', compact('projects', 'years', 'type', 'year', 'types'));
    }
}
