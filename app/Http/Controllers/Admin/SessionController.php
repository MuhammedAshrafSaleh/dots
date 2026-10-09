<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetAdminLocale;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /** Log the admin out and return to the login screen. */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /** Switch the admin-area language (stored in the session) and go back. */
    public function language(Request $request, string $locale): RedirectResponse
    {
        if (Locale::isSupported($locale)) {
            $request->session()->put(SetAdminLocale::SESSION_KEY, $locale);
        }

        return redirect()->back(fallback: route('admin.login'));
    }
}
