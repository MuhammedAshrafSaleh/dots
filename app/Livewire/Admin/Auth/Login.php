<?php

namespace App\Livewire\Admin\Auth;

use App\Support\Auth\LoginThrottle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin-auth')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = true;

    /** Attempts remaining before the lock; shown in the error alert. */
    public ?int $attemptsLeft = null;

    public function login(): void
    {
        $this->validate();

        $throttle = LoginThrottle::for('admin', $this->email, request()->ip());
        $throttle->ensureNotLocked();

        $ok = Auth::guard('admin')->attempt([
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => true,
        ], $this->remember);

        if (! $ok) {
            $throttle->hit();
            $this->attemptsLeft = $throttle->remaining();
            $this->reset('password');

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $throttle->clear();

        $user = Auth::guard('admin')->user();
        $user?->forceFill(['last_login_at' => now()])->saveQuietly();

        session()->regenerate();

        $this->redirectRoute('admin.dashboard', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.login');
    }
}
