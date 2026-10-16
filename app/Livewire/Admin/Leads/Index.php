<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Enums\QuoteSource;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\QuoteRequest;
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
    public string $source = '';

    #[Url(except: '', history: false)]
    public string $from = '';

    #[Url(except: '', history: false)]
    public string $to = '';

    // Drawer state.
    public bool $showDrawer = false;

    public ?int $editingId = null;

    public string $contact_name = '';

    public string $contact_mobile = '';

    public string $status = 'new';

    public string $admin_notes = '';

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'source', 'from', 'to', 'tab');
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('leads.view');
        $lead = QuoteRequest::findOrFail($id);

        $this->editingId = $lead->id;
        $this->contact_name = (string) $lead->contact_name;
        $this->contact_mobile = (string) $lead->contact_mobile;
        $this->status = $lead->status->value;
        $this->admin_notes = (string) $lead->admin_notes;

        $this->resetErrorBag();
        $this->showDrawer = true;
    }

    public function save(): void
    {
        $this->authorize('leads.update');

        $data = $this->validate([
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        QuoteRequest::findOrFail($this->editingId)->update([
            'contact_name' => $data['contact_name'] ?: null,
            'contact_mobile' => $data['contact_mobile'] ?: null,
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?: null,
        ]);

        $this->showDrawer = false;
        $this->dispatch('toast', tone: 'success', title: __('admin.leads.saved'));
    }

    /**
     * Base query shared by the table and the CSV export.
     *
     * @return Builder<QuoteRequest>
     */
    private function baseQuery(): Builder
    {
        return QuoteRequest::query()
            ->with('productVariant.product')
            ->when($this->tab !== 'all', fn (Builder $q) => $q->where('status', $this->tab))
            ->when($this->source !== '', fn (Builder $q) => $q->where('source', $this->source))
            ->when($this->from !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $this->to))
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('contact_name', 'like', $like)
                    ->orWhere('contact_mobile', 'like', $like)
                    ->orWhere('page_url', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** @return array<string, int> */
    public function tabCounts(): array
    {
        $counts = QuoteRequest::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $out = ['all' => (int) $counts->sum()];
        foreach (LeadStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $out;
    }

    public function export(): StreamedResponse
    {
        $this->authorize('leads.view');

        $locale = app()->getLocale();
        $rows = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($rows, $locale): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('admin.leads.col_customer', [], $locale),
                __('admin.leads.drawer_mobile', [], $locale),
                __('admin.leads.col_source', [], $locale),
                __('admin.leads.col_ref', [], $locale),
                __('admin.leads.col_date', [], $locale),
                __('admin.leads.col_status', [], $locale),
            ]);

            foreach ($rows as $lead) {
                fputcsv($out, [
                    $lead->contact_name,
                    $lead->contact_mobile,
                    __('admin.leads.source_'.$lead->source->value, [], $locale),
                    $this->refFor($lead),
                    $lead->created_at?->format('Y-m-d H:i'),
                    __('admin.leads.status_'.$lead->status->value, [], $locale),
                ]);
            }

            fclose($out);
        }, 'whatsapp-leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function refFor(QuoteRequest $lead): string
    {
        if ($lead->productVariant?->product) {
            return $lead->productVariant->product->getTranslation('name', app()->getLocale(), false);
        }

        return (string) ($lead->page_url ?? '—');
    }

    public function render(): View
    {
        return view('livewire.admin.leads.index', [
            'rows' => $this->baseQuery()->paginate(12),
            'counts' => $this->tabCounts(),
            'sources' => array_map(
                fn (QuoteSource $s) => ['value' => $s->value, 'label' => __('admin.leads.source_'.$s->value)],
                QuoteSource::cases(),
            ),
            'statuses' => array_map(
                fn (LeadStatus $s) => ['value' => $s->value, 'label' => __('admin.leads.status_'.$s->value)],
                LeadStatus::cases(),
            ),
        ]);
    }
}
