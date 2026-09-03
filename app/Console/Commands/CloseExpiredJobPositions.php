<?php

namespace App\Console\Commands;

use App\Models\JobPosition;
use Illuminate\Console\Command;

class CloseExpiredJobPositions extends Command
{
    protected $signature = 'job-positions:close-expired';

    protected $description = 'Automatically close open job positions whose application deadline (until date/time) has passed.';

    public function handle(): int
    {
        $count = JobPosition::query()
            ->where('is_open', true)
            ->whereNotNull('until')
            ->whereRaw("TIMESTAMP(until, COALESCE(until_time, '23:59:59')) < ?", [now()])
            ->update(['is_open' => false]);

        $this->info("Closed {$count} expired job position(s).");

        return self::SUCCESS;
    }
}
