<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public bool $showAdd = false;

    public bool $invite = true;

    public string $name = '';

    public string $addEmail = '';

    public string $tempPassword = '';

    public string $search = '';

    public function openAdd(): void
    {
        $this->name = '';
        $this->addEmail = '';
        $this->tempPassword = '';
        $this->invite = true;
        $this->showAdd = true;
    }

    public function addAdmin(): void
    {
        $this->authorize('users.create');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'addEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];

        if (! $this->invite) {
            $rules['tempPassword'] = ['required', 'string', 'min:10', 'max:255'];
        }

        $this->validate($rules, [], [
            'addEmail' => __('admin.users.work_email'),
            'tempPassword' => __('admin.users.temp_password'),
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->addEmail,
            'password' => Hash::make($this->invite ? Str::random(32) : $this->tempPassword),
            'is_active' => true,
        ]);

        $user->syncRoles([Role::findByName('Admin', 'web')]);

        if ($this->invite) {
            Password::broker()->sendResetLink(['email' => $this->addEmail]);
            $this->dispatch('toast', tone: 'success', title: __('admin.users.invite_sent'));
        } else {
            $this->dispatch('toast', tone: 'success', title: __('admin.users.added'));
        }

        $this->showAdd = false;
    }

    public function deactivate(int $id): void
    {
        $this->authorize('users.update');
        $this->abortIfSelf($id);

        User::findOrFail($id)->update(['is_active' => false]);
        $this->dispatch('toast', tone: 'success', title: __('admin.users.deactivated'));
    }

    public function reactivate(int $id): void
    {
        $this->authorize('users.update');

        User::findOrFail($id)->update(['is_active' => true]);
        $this->dispatch('toast', tone: 'success', title: __('admin.users.reactivated'));
    }

    public function resetPassword(int $id): void
    {
        $this->authorize('users.update');

        $user = User::findOrFail($id);
        Password::broker()->sendResetLink(['email' => $user->email]);
        $this->dispatch('toast', tone: 'success', title: __('admin.users.reset_sent'));
    }

    public function remove(int $id): void
    {
        $this->authorize('users.delete');
        $this->abortIfSelf($id);

        User::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.users.removed'));
    }

    public function render(): View
    {
        $query = User::query()
            ->when($this->search !== '', function ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderByDesc('created_at');

        return view('livewire.admin.users.index', [
            'rows' => $query->get(),
            'me' => auth('admin')->id(),
        ]);
    }

    private function abortIfSelf(int $id): void
    {
        if ($id === auth('admin')->id()) {
            $this->dispatch('toast', tone: 'error', title: __('admin.users.cannot_self'));
            $this->dispatch('toast'); // trigger re-render without further action
        }
        abort_if($id === auth('admin')->id(), 422, __('admin.users.cannot_self'));
    }
}
