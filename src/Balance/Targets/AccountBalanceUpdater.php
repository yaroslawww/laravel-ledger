<?php


namespace LaravelLedger\Balance\Targets;

use LaravelLedger\Models\LedgerAction;

class AccountBalanceUpdater extends AbstractBalanceUpdater
{
    public function __construct(LedgerAction $action)
    {
        parent::__construct($action);
    }

    public function addSum()
    {
        $account = $this->action->account;
        $account->balance = $account->balance + $this->action->sum;
        $account->save();
    }

    public function subtractSum()
    {
        $account = $this->action->account;
        $account->balance = $account->balance - $this->action->sum;
        $account->save();
    }
}
