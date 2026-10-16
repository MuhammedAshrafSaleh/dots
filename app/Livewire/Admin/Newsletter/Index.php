<?php

namespace App\Livewire\Admin\Newsletter;

use App\Enums\NewsletterSource;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
    public string $lang = '';

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    public function updatingLang(): void
    {
        $this->resetPage();
    }

    public function unsubscribe(int $id): void
    {
        $this->authorize('newsletter.view');
        NewsletterSubscriber::findOrFail($id)->update(['unsubscribed_at' => now()]);
        $this->dispatch('toast', tone: 'success', title: __('admin.newsletter.unsubscribed_toast'));
    }

    public function resubscribe(int $id): void
    {
        $this->authorize('newsletter.view');
        NewsletterSubscriber::findOrFail($id)->update(['unsubscribed_at' => null]);
        $this->dispatch('toast', tone: 'success', title: __('admin.newsletter.resubscribed_toast'));
    }

    public function delete(int $id): void
    {
        $this->authorize('newsletter.view');
        NewsletterSubscriber::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.newsletter.deleted'));
    }

    /**
     * Base query shared by the table and the CSV export.
     *
     * @return Builder<NewsletterSubscriber>
     */
    private function baseQuery(): Builder
    {
        return NewsletterSubscriber::query()
            ->when($this->tab === 'subscribed', fn (Builder $q) => $q->whereNull('unsubscribed_at'))
            ->when($this->tab === 'unsubscribed', fn (Builder $q) => $q->whereNotNull('unsubscribed_at'))
            ->when($this->source !== '', fn (Builder $q) => $q->where('source', $this->source))
            ->when($this->lang !== '', fn (Builder $q) => $q->where('locale', $this->lang))
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where('email', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** @return array<string, int> */
    public function tabCounts(): array
    {
        $total = NewsletterSubscriber::query()->count();
        $unsub = NewsletterSubscriber::query()->whereNotNull('unsubscribed_at')->count();

        return ['all' => $total, 'subscribed' => $total - $unsub, 'unsubscribed' => $unsub];
    }

    public function export(): StreamedResponse
    {
        $this->authorize('newsletter.export');

        $locale = app()->getLocale();
        $rows = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($rows, $locale): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('admin.newsletter.col_email', [], $locale),
                __('admin.newsletter.col_language', [], $locale),
                __('admin.newsletter.col_source', [], $locale),
                __('admin.newsletter.col_date', [], $locale),
                __('admin.newsletter.col_status', [], $locale),
            ]);

            foreach ($rows as $sub) {
                fputcsv($out, [
                    $sub->email,
                    $sub->locale,
                    __('admin.newsletter.source_'.$sub->source->value, [], $locale),
                    $sub->created_at?->format('Y-m-d H:i'),
                    $sub->unsubscribed_at
                        ? __('admin.newsletter.status_unsubscribed', [], $locale)
                        : __('admin.newsletter.status_subscribed', [], $locale),
                ]);
            }

            fclose($out);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('livewire.admin.newsletter.index', [
            'rows' => $this->baseQuery()->paginate(12),
            'counts' => $this->tabCounts(),
            'sources' => array_map(
                fn (NewsletterSource $s) => ['value' => $s->value, 'label' => __('admin.newsletter.source_'.$s->value)],
                NewsletterSource::cases(),
            ),
        ]);
    }
}
