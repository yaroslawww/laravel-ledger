<?php

namespace LaravelLedger\Commands;

use Illuminate\Console\Command;
use LaravelLedger\Models\LedgerAccount;
use LaravelLedger\Models\LedgerBook;

class RegenerateBalanceCommand extends Command
{
    public $signature = 'ledger:regenerate-accounts-balance
     {--A|account= : Account ID}
     {--L|ledger= : Ledger ID, account option will be skipped}
     ';

    public $description = 'Regenerate account balances';

    public function handle()
    {
        $accountId = (int) $this->option('account');
        $legerId = (int) $this->option('ledger');

        if ($legerId) {
            return $this->updateLedger($legerId);
        }

        if ($accountId) {
            return $this->updateAccount($accountId);
        }

        return $this->updateAll();
    }

    protected function updateLedger(int $id)
    {
        /** @var LedgerBook $ledger */
        $ledger = LedgerBook::findOrFail($id);
        $ledger->regenerateAccountsBalance();
    }

    protected function updateAccount(int $id)
    {
        /** @var LedgerAccount $account */
        $account = LedgerAccount::findOrFail($id);
        $account->regenerateBalance();
    }

    protected function updateAll()
    {
        /** @var LedgerBook $ledger */
        foreach (LedgerBook::cursor() as $ledger) {
            $ledger->regenerateAccountsBalance();
        }
    }
}
