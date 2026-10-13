<?php

use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\Service;
use App\Support\Slug;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withoutVite();
});

function makeProject(array $attrs = []): Project
{
    $title = $attrs['title'] ?? ['en' => 'Test project', 'ar' => 'مشروع تجريبي'];

    return Project::create(array_merge([
        'status' => ProjectStatus::Current,
        'type' => ProjectType::Residential,
        'title' => $title,
        'slug' => Slug::fromTranslations($title),
        'location' => ['en' => 'Cairo', 'ar' => 'القاهرة'],
        'progress' => 50,
        'stage' => ProjectStage::Installation,
        'phase_label' => ['en' => 'Installation', 'ar' => 'التركيب'],
        'scope_summary' => ['en' => 'Full scope', 'ar' => 'نطاق كامل'],
        'about' => ['en' => '<p>About this project.</p>', 'ar' => '<p>عن المشروع.</p>'],
        'is_featured' => false,
        'sort_order' => 0,
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'expected_completion' => Carbon::parse('2026-12-01'),
    ], $attrs));
}

it('renders the current projects page', function () {
    makeProject(['title' => ['en' => 'Villa Palm Hills', 'ar' => 'فيلا بالم هيلز'], 'is_featured' => true]);

    $this->get('/en/projects')->assertOk()->assertSee('Villa Palm Hills');
    $this->get('/ar/projects')->assertOk()->assertSee('فيلا بالم هيلز');
});

it('filters current projects by type via query string', function () {
    makeProject(['title' => ['en' => 'Residential one', 'ar' => 'سكني واحد'], 'type' => ProjectType::Residential]);
    makeProject(['title' => ['en' => 'Commercial one', 'ar' => 'تجاري واحد'], 'type' => ProjectType::Commercial]);

    $res = $this->get('/en/projects?type=residential');
    $res->assertOk()->assertSee('Residential one')->assertDontSee('Commercial one');
});

it('shows a featured lead card when type is all', function () {
    makeProject(['title' => ['en' => 'Featured project', 'ar' => 'مشروع مميز'], 'is_featured' => true]);

    $this->get('/en/projects')->assertOk()->assertSee('Featured project');
});

it('renders the previous projects page', function () {
    makeProject([
        'title' => ['en' => 'Done villa', 'ar' => 'فيلا منجزة'],
        'status' => ProjectStatus::Completed,
        'completed_year' => 2025,
        'progress' => 100,
        'stage' => null,
    ]);

    $this->get('/en/projects/previous')->assertOk()->assertSee('Done villa');
    $this->get('/ar/projects/previous')->assertOk()->assertSee('فيلا منجزة');
});

it('filters previous projects by type', function () {
    makeProject(['title' => ['en' => 'Done res', 'ar' => 'سكني منجز'], 'status' => ProjectStatus::Completed, 'type' => ProjectType::Residential, 'completed_year' => 2025, 'progress' => 100, 'stage' => null]);
    makeProject(['title' => ['en' => 'Done com', 'ar' => 'تجاري منجز'], 'status' => ProjectStatus::Completed, 'type' => ProjectType::Commercial, 'completed_year' => 2025, 'progress' => 100, 'stage' => null]);

    $this->get('/en/projects/previous?type=residential')
        ->assertOk()
        ->assertSee('Done res')
        ->assertDontSee('Done com');
});

it('filters previous projects by year', function () {
    makeProject(['title' => ['en' => 'Project 2025', 'ar' => 'مشروع 2025'], 'status' => ProjectStatus::Completed, 'completed_year' => 2025, 'progress' => 100, 'stage' => null]);
    makeProject(['title' => ['en' => 'Project 2024', 'ar' => 'مشروع 2024'], 'status' => ProjectStatus::Completed, 'completed_year' => 2024, 'progress' => 100, 'stage' => null]);

    $this->get('/en/projects/previous?year=2025')
        ->assertOk()
        ->assertSee('Project 2025')
        ->assertDontSee('Project 2024');
});

it('paginates previous projects at 8 per page', function () {
    for ($i = 1; $i <= 10; $i++) {
        makeProject([
            'title' => ['en' => "Done project {$i}", 'ar' => "مشروع منجز {$i}"],
            'status' => ProjectStatus::Completed,
            'completed_year' => 2025,
            'progress' => 100,
            'stage' => null,
            'sort_order' => $i,
        ]);
    }

    $this->get('/en/projects/previous')->assertOk()->assertSee('Done project 1');
    // Page 2 should exist
    $this->get('/en/projects/previous?page=2')->assertOk()->assertSee('Done project');
});

it('renders a current project detail page', function () {
    $project = makeProject([
        'title' => ['en' => 'Villa Detail', 'ar' => 'تفاصيل الفيلا'],
        'stage' => ProjectStage::Commissioning,
        'progress' => 80,
    ]);

    $this->get('/en/projects/villa-detail')->assertOk()->assertSee('Villa Detail');
    $this->get('/ar/projects/'.rawurlencode('تفاصيل-الفيلا'))->assertOk()->assertSee('تفاصيل الفيلا');
});

it('renders a completed project detail page', function () {
    $project = makeProject([
        'title' => ['en' => 'Completed villa', 'ar' => 'فيلا مكتملة'],
        'status' => ProjectStatus::Completed,
        'completed_year' => 2024,
        'progress' => 100,
        'stage' => null,
        'challenge' => ['en' => 'Tight schedule', 'ar' => 'جدول ضيق'],
        'solution' => ['en' => 'Phased plan', 'ar' => 'خطة مرحلية'],
        'result' => ['en' => 'On time', 'ar' => 'في الموعد'],
    ]);

    $this->get('/en/projects/completed-villa')
        ->assertOk()
        ->assertSee('Completed villa')
        ->assertSee('Tight schedule');
});

it('returns 404 for unknown project slug', function () {
    $this->get('/en/projects/no-such-project')->assertNotFound();
});

it('shows services used on detail page', function () {
    $project = makeProject(['title' => ['en' => 'Service project', 'ar' => 'مشروع خدمة']]);
    $service = Service::create([
        'number' => '01',
        'title' => ['en' => 'Lighting design', 'ar' => 'تصميم الإضاءة'],
        'slug' => ['en' => 'lighting-design', 'ar' => 'تصميم-الاضاءة'],
        'excerpt' => ['en' => 'desc', 'ar' => 'وصف'],
        'description' => ['en' => 'desc', 'ar' => 'وصف'],
        'included_title' => ['en' => 'pkg', 'ar' => 'باقة'],
        'duration_text' => ['en' => '3 weeks', 'ar' => '3 أسابيع'],
        'show_in_footer' => true,
        'sort_order' => 0,
        'is_active' => true,
    ]);
    $project->services()->attach($service);

    $this->get('/en/projects/service-project')
        ->assertOk()
        ->assertSee('Lighting design');
});
