<?php

namespace App\Console\Commands;

use App\Support\AuditTrail;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Remove audit logs older than the retention period';

    public function handle(): int
    {
        $removed = AuditTrail::prune();
        $this->info($removed === 0
            ? 'No audit logs were old enough to remove.'
            : "Removed {$removed} audit log(s) past the retention period.");

        return self::SUCCESS;
    }
}
