<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin-auth')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate();

        // Always report success: never reveal whether an email is registered.
        Password::broker('users')->sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.admin.auth.forgot-password');
    }
}
