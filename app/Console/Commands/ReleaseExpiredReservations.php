<?php

namespace App\Console\Commands;

use App\Actions\ReleaseExpiredReservations as ReleaseAction;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'orders:release-reservations';

    protected $description = 'Cancel InstaPay orders whose transfer window expired and restore their stock.';

    public function handle(ReleaseAction $action): int
    {
        $count = $action->handle();

        $this->info("Released {$count} expired reservation(s).");

        return self::SUCCESS;
    }
}
