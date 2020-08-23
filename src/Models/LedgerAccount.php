<?php


namespace LaravelLedger\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelLedger\Balance\Targets\AccountBalanceUpdater;
use LaravelLedger\Enums\LedgerActionType;

class LedgerAccount extends Model
{
    protected $table = 'ldgr_accounts';

    protected $guarded = [];

    public function ledger()
    {
        return $this->belongsTo(LedgerBook::class, 'ledger_id', 'id');
    }

    public function actions()
    {
        return $this->hasMany(LedgerAction::class, 'account_id', 'id');
    }

    public function regenerateBalance()
    {
        $this->balance = 0;
        $this->save();
        /** @var LedgerAction $action */
        foreach ($this->actions()->cursor() as $action) {
            $action->addToBalance([AccountBalanceUpdater::class]);
        }
        $this->refresh();
    }

    public function getBalanceAttribute($value)
    {
        return (int)$value;
    }

    public function addIncome(int $sum, \DateTime $datetime = null, array $options = [])
    {
        $additional = [];
        if (isset($options['target_id']) && isset($options['target_type'])) {
            $additional['target_id'] = $options['target_id'];
            $additional['target_type'] = $options['target_type'];
        }
        if (isset($options['meta'])) {
            $additional['meta'] = (is_string($options['meta']) ? $options['meta'] : json_encode($options['meta']));
        }

        $this->actions()->create(array_merge($additional, [
            'type' => LedgerActionType::income(),
            'sum' => $sum,
            'memo' => isset($options['memo'])?$options['memo']:null,
            'datetime' => $datetime?:now(),
        ]));

        return $this->refresh();
    }

    public function addExpense(int $sum, \DateTime $datetime = null, array $options = [])
    {
        $additional = [];
        if (isset($options['target_id']) && isset($options['target_type'])) {
            $additional['target_id'] = $options['target_id'];
            $additional['target_type'] = $options['target_type'];
        }
        if (isset($options['meta'])) {
            $additional['meta'] = (is_string($options['meta']) ? $options['meta'] : json_encode($options['meta']));
        }

        $this->actions()->create(array_merge($additional, [
            'type' => LedgerActionType::expense(),
            'sum' => $sum,
            'memo' => isset($options['memo'])?$options['memo']:null,
            'datetime' => $datetime?:now(),
        ]));

        return $this->refresh();
    }
}
