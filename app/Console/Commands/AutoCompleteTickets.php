<?php

namespace App\Console\Commands;

use App\Services\InstallationBookingService;
use Illuminate\Console\Command;

class AutoCompleteTickets extends Command
{
    protected $signature = 'tickets:auto-complete';
    protected $description = 'Automatically complete tickets whose scheduled end has passed';

    public function handle(InstallationBookingService $bookingService): int
    {
        $count = $bookingService->autoCompleteOverdueTickets();

        $this->info("Auto completed {$count} ticket(s).");

        return self::SUCCESS;
    }
}
