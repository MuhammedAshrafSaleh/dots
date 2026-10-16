<?php

namespace App\Livewire\Admin\Consultations;

use App\Enums\ConsultationProjectType;
use App\Enums\LeadStatus;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\ConsultationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    #[Url(except: 'all', history: false)]
    public string $tab = 'all';

    #[Url(except: '', history: false)]
    public string $type = '';

    #[Url(except: '', history: false)]
    public string $from = '';

    #[Url(except: '', history: false)]
    public string $to = '';

    // Drawer state.
    public bool $showDrawer = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $mobile = '';

    public string $status = 'new';

    public string $admin_notes = '';

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'from', 'to', 'tab');
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('consultations.view');
        $req = ConsultationRequest::findOrFail($id);

        $this->editingId = $req->id;
        $this->name = (string) $req->name;
        $this->mobile = (string) $req->mobile;
        $this->status = $req->status->value;
        $this->admin_notes = (string) $req->admin_notes;

        $this->resetErrorBag();
        $this->showDrawer = true;
    }

    public function save(): void
    {
        $this->authorize('consultations.update');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:30'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        ConsultationRequest::findOrFail($this->editingId)->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?: null,
        ]);

        $this->showDrawer = false;
        $this->dispatch('toast', tone: 'success', title: __('admin.consultations.saved'));
    }

    /**
     * Base query shared by the table and the CSV export.
     *
     * @return Builder<ConsultationRequest>
     */
    private function baseQuery(): Builder
    {
        return ConsultationRequest::query()
            ->with('service')
            ->when($this->tab !== 'all', fn (Builder $q) => $q->where('status', $this->tab))
            ->when($this->type !== '', fn (Builder $q) => $q->where('project_type', $this->type))
            ->when($this->from !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $this->to))
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('notes', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** @return array<string, int> */
    public function tabCounts(): array
    {
        $counts = ConsultationRequest::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $out = ['all' => (int) $counts->sum()];
        foreach (LeadStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $out;
    }

    public function export(): StreamedResponse
    {
        $this->authorize('consultations.view');

        $locale = app()->getLocale();
        $rows = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($rows, $locale): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('admin.consultations.col_customer', [], $locale),
                __('admin.consultations.drawer_mobile', [], $locale),
                __('admin.consultations.col_type', [], $locale),
                __('admin.consultations.drawer_area', [], $locale),
                __('admin.consultations.drawer_service', [], $locale),
                __('admin.consultations.col_date', [], $locale),
                __('admin.consultations.col_status', [], $locale),
            ]);

            foreach ($rows as $req) {
                fputcsv($out, [
                    $req->name,
                    $req->mobile,
                    __('admin.consultations.type_'.$req->project_type->value, [], $locale),
                    __('admin.consultations.area_'.$req->area_range->value, [], $locale),
                    $req->service?->getTranslation('title', $locale, false),
                    $req->created_at?->format('Y-m-d H:i'),
                    __('admin.leads.status_'.$req->status->value, [], $locale),
                ]);
            }

            fclose($out);
        }, 'consultations-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('livewire.admin.consultations.index', [
            'rows' => $this->baseQuery()->paginate(12),
            'counts' => $this->tabCounts(),
            'types' => array_map(
                fn (ConsultationProjectType $t) => ['value' => $t->value, 'label' => __('admin.consultations.type_'.$t->value)],
                ConsultationProjectType::cases(),
            ),
            'statuses' => array_map(
                fn (LeadStatus $s) => ['value' => $s->value, 'label' => __('admin.leads.status_'.$s->value)],
                LeadStatus::cases(),
            ),
        ]);
    }
}
