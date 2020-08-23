<?php


namespace LaravelLedger\Balance\Targets;

use LaravelLedger\Enums\LedgerActionType;
use LaravelLedger\Exceptions\LaravelLedgerException;
use LaravelLedger\Models\LedgerAction;

abstract class AbstractBalanceUpdater
{
    protected LedgerAction $action;

    public function __construct(LedgerAction $action)
    {
        $this->action = $action->refresh();
    }

    public function onCreated()
    {
        switch ((string)$this->action->type) {
            case (string)LedgerActionType::income():
                $this->addSum();

                break;
            case (string)LedgerActionType::expense():
                $this->subtractSum();

                break;
            default:
                throw new LaravelLedgerException('Not valid LedgerActionType');
        }
    }

    public function onDeleted()
    {
        switch ((string)$this->action->type) {
            case (string)LedgerActionType::income():
                $this->subtractSum();

                break;
            case (string)LedgerActionType::expense():
                $this->addSum();

                break;
            default:
                throw new LaravelLedgerException('Not valid LedgerActionType');
        }
    }

    abstract public function addSum();

    abstract public function subtractSum();
}
