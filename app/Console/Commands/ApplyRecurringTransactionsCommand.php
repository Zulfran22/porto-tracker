<?php

namespace App\Console\Commands;

use App\Actions\ApplyRecurringTransactions;
use App\Models\RecurringTransaction;
use Illuminate\Console\Command;

class ApplyRecurringTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:apply';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply today\'s active recurring transactions for every user (idempotent)';

    /**
     * Execute the console command.
     */
    public function handle(ApplyRecurringTransactions $action): int
    {
        $userIds = RecurringTransaction::where('aktif', true)->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $result = $action->execute($userId);
            $this->info("User #{$userId}: {$result['applied']} applied, {$result['skipped']} skipped.");
        }

        return self::SUCCESS;
    }
}
