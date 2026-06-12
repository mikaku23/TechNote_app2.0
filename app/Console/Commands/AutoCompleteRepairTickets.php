<?php

namespace App\Console\Commands;

use App\Services\RepairService;
use Illuminate\Console\Command;

class AutoCompleteRepairTickets extends Command
{
    protected $signature = 'repair-tickets:auto-complete';
    protected $description = 'Automatically complete repair tickets whose scheduled end has passed';

    public function handle(RepairService $repairService): int
    {
        $count = $repairService->autoCompleteOverdueTickets();

        $this->info("Auto completed {$count} repair ticket(s).");

        return self::SUCCESS;
    }
}
