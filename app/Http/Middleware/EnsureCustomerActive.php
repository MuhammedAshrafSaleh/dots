<?php

namespace App\Http\Middleware;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks a disabled customer out of the signed-in account area. Disabling in
 * the admin can't reach into another person's active session directly, so the
 * admin invalidates their remember token and this middleware ends the session
 * on the customer's next authenticated request (the "disable logs them out"
 * guarantee from the phase plan).
 */
class EnsureCustomerActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ?Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($customer !== null && $customer->status === CustomerStatus::Disabled) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $locale = $request->route('locale') ?? Locale::DEFAULT;

            return redirect()->route('login', ['locale' => $locale])
                ->with('status', __('account.login.account_disabled'));
        }

        return $next($request);
    }
}
