<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = Auth::guard('admin')->user();

        return view('livewire.admin.dashboard', [
            'name' => $user?->name,
        ]);
    }
}
