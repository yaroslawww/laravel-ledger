<?php


namespace LaravelLedger\Tests\Ledger;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelLedger\Models\LedgerAccount;
use LaravelLedger\Models\LedgerAction;
use LaravelLedger\Models\LedgerBook;
use LaravelLedger\Tests\TestCase;

class BusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerBook $ledger;
    protected LedgerAccount $accountGBP;
    protected LedgerAccount $accountUSD;

    protected function prepareDatabase()
    {
        $this->ledger = factory(LedgerBook::class)->create([
            'name' => 'TestLedger',
        ]);

        $this->accountGBP = factory(LedgerAccount::class)->create([
            'ledger_id' => $this->ledger->id,
            'name' => 'account_USD',
            'currency' => 'GBP',
        ]);
        $this->accountUSD = factory(LedgerAccount::class)->create([
            'ledger_id' => $this->ledger->id,
            'name' => 'account_USD',
        ]);
    }

    /** @test */
    public function quickBalanceManager()
    {
        $this->prepareDatabase();

        $this->accountUSD->addIncome(300, now(), [
            'meta' => ['test' => 'test'],
            'target_id' => 1,
            'target_type' => LedgerAction::class,
        ]);
        $this->accountUSD->addIncome(123);
        $this->accountUSD->addExpense(100, null, [
            'meta' => ['test' => 'test'],
            'target_id' => 1,
            'target_type' => LedgerAction::class,
        ]);

        $this->assertEquals(323, $this->accountUSD->balance);
    }
}
