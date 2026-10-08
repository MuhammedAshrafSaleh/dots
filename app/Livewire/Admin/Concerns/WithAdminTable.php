<?php

namespace App\Livewire\Admin\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Shared list-screen behaviour (mirrors AList): search, sortable columns,
 * row selection and bulk actions, on top of Livewire pagination. Screens set
 * the query; this trait owns the UI state.
 */
trait WithAdminTable
{
    use WithPagination;

    #[Url(except: '', history: false)]
    public string $search = '';

    /** Render pagination in the admin design (.d-pages). */
    public function paginationView(): string
    {
        return 'livewire.admin-pagination';
    }

    public ?string $sortCol = null;

    public string $sortDir = 'asc';

    /** @var array<int, int|string> */
    public array $selected = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortCol === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortCol = $column;
        $this->sortDir = 'asc';
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Toggle "select all" for the given page of ids.
     *
     * @param  array<int, int|string>  $ids
     */
    public function toggleSelectAll(array $ids): void
    {
        $allSelected = ! array_diff($ids, $this->selected);

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique([...$this->selected, ...$ids]));
    }
}
