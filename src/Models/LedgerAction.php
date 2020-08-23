<?php


namespace LaravelLedger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LaravelLedger\Balance\Targets\AbstractBalanceUpdater;
use LaravelLedger\Balance\Targets\AccountBalanceUpdater;
use LaravelLedger\Exceptions\LaravelLedgerException;

class LedgerAction extends Model
{
    protected $table = 'ldgr_actions';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    protected $casts = [
        'datetime' => 'datetime',
    ];

    protected $balanceUpdaters = [
        AccountBalanceUpdater::class,
    ];

    protected static function booted()
    {
        static::creating(function ($action) {
            /** @var self $action */
            $action->{$action->getKeyName()} = (string)Str::uuid();
            if (! $action->datetime) {
                $action->datetime = now();
            }
        });

        static::created(function ($action) {
            /** @var self $action */
            $action->addToBalance();
        });

        static::deleting(function ($action) {
            /** @var self $action */
            $action->removeFromBalance();
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id', 'id');
    }

    public function addToBalance(array $balanceUpdaters = [])
    {
        return $this->processBalanceTargets('onCreated', $balanceUpdaters);
    }

    public function removeFromBalance(array $balanceUpdaters = [])
    {
        return $this->processBalanceTargets('onDeleted', $balanceUpdaters);
    }

    protected function processBalanceTargets(string $method, array $balanceUpdaters = [])
    {
        if (empty($balanceUpdaters)) {
            $balanceUpdaters = $this->balanceUpdaters;
        }
        foreach ($balanceUpdaters as $balanceUpdaterClass) {
            $balanceUpdater = new $balanceUpdaterClass($this);
            if (! ($balanceUpdater instanceof AbstractBalanceUpdater)) {
                throw new LaravelLedgerException('$balanceTarget should be instance of AbstractBalanceUpdater');
            }
            $balanceUpdater->{$method}();
        }
    }
}
