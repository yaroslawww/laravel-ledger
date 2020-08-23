<?php


namespace LaravelLedger\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerBook extends Model
{
    protected $table = 'ldgr_ledgers';

    protected $guarded = [];


    public function accounts()
    {
        return $this->hasMany(LedgerAccount::class, 'ledger_id', 'id');
    }


    public function actions()
    {
        return $this->hasManyThrough(
            LedgerAction::class,
            LedgerAccount::class,
            'ledger_id',
            'account_id',
            'id',
            'id'
        );
    }

    public function regenerateAccountsBalance()
    {
        /** @var LedgerAccount $account */
        foreach ($this->accounts()->cursor() as $account) {
            $account->regenerateBalance();
        }
        $this->refresh();
    }
}
