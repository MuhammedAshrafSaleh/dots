<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Order;

/**
 * Attaches a customer's guest orders to their account (PRD §9). A guest order
 * links only when BOTH its contact mobile and contact email match the customer
 * — a mobile-only match never links, so one person's orders can't leak onto
 * another account that happens to share a phone number. Run at registration and
 * when a guest chooses "save your details" on the confirmation screen.
 */
class LinkGuestOrders
{
    /** @return int the number of guest orders attached */
    public function handle(Customer $customer): int
    {
        $email = mb_strtolower(trim((string) $customer->email));
        $mobile = (string) $customer->mobile;

        if ($email === '' || $mobile === '') {
            return 0;
        }

        return Order::query()
            ->whereNull('customer_id')
            ->where('contact_mobile', $mobile)
            ->whereRaw('LOWER(contact_email) = ?', [$email])
            ->update(['customer_id' => $customer->id]);
    }
}
