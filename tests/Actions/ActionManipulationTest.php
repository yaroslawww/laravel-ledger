<?php


namespace LaravelLedger\Tests\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelLedger\Balance\Targets\AccountBalanceUpdater;
use LaravelLedger\Commands\RegenerateBalanceCommand;
use LaravelLedger\Enums\LedgerActionType;
use LaravelLedger\Exceptions\LaravelLedgerException;
use LaravelLedger\Models\LedgerAccount;
use LaravelLedger\Models\LedgerAction;
use LaravelLedger\Models\LedgerBook;
use LaravelLedger\Tests\TestCase;

class ActionManipulationTest extends TestCase
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
    public function on_action_event_account_balance_triggered()
    {
        $this->prepareDatabase();

        $someActionIncome = factory(LedgerAction::class)->create([
            'account_id' => $this->accountUSD->id,
            'type' => LedgerActionType::income(),
            'sum' => 123,
        ]);

        $this->accountUSD->refresh();
        $this->assertEquals(123, $this->accountUSD->balance);

        $this->accountUSD->actions()->create([
            'account_id' => $this->accountUSD->id,
            'type' => LedgerActionType::income(),
            'sum' => 27,
        ]);

        $this->accountUSD->refresh();
        $this->assertEquals(150, $this->accountUSD->balance);

        $someActionExpense = LedgerAction::create([
            'account_id' => $this->accountUSD->id,
            'type' => LedgerActionType::expense(),
            'sum' => 23,
        ]);

        $this->accountUSD->refresh();
        $this->assertEquals(127, $this->accountUSD->balance);

        $this->accountUSD->actions()->create([
            'account_id' => $this->accountUSD->id,
            'type' => LedgerActionType::expense(),
            'sum' => 100,
        ]);

        $this->accountUSD->refresh();
        $this->assertEquals(27, $this->accountUSD->balance);

        $someActionIncome->delete();
        $this->accountUSD->refresh();
        $this->assertEquals(-96, $this->accountUSD->balance);

        $someActionExpense->delete();
        $this->accountUSD->refresh();
        $this->assertEquals(-73, $this->accountUSD->balance);

        $this->assertCount(2, $this->accountUSD->actions);
    }

    public function setBalanceZero()
    {
        $this->accountUSD->balance = 0;
        $this->accountUSD->save();
        $this->assertEquals(0, $this->accountUSD->balance);
    }

    /** @test */
    public function refresh_account_balance()
    {
        $this->prepareDatabase();
        factory(LedgerAction::class, 4)->create([
            'account_id' => $this->accountUSD->id,
        ]);
        $this->accountUSD->refresh();

        $balance = $this->accountUSD->balance;

        $this->assertNotEquals(0, $balance);

        $this->setBalanceZero();

        $this->accountUSD->regenerateBalance();
        $this->assertEquals($balance, $this->accountUSD->balance);

        $this->setBalanceZero();

        $this->ledger->regenerateAccountsBalance();
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);
    }

    /** @test */
    public function console_refresh_balance()
    {
        $this->prepareDatabase();
        factory(LedgerAction::class, 4)->create([
            'account_id' => $this->accountUSD->id,
        ]);
        $this->accountUSD->refresh();

        $balance = $this->accountUSD->balance;
        $this->assertNotEquals(0, $balance);

        $this->setBalanceZero();

        $this->artisan('ledger:regenerate-accounts-balance');
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);

        $this->setBalanceZero();
        $this->artisan('ledger:regenerate-accounts-balance', [
            '-A' => $this->accountUSD->id,
        ]);
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);

        $this->setBalanceZero();
        $this->artisan('ledger:regenerate-accounts-balance', [
            '--account' => $this->accountUSD->id,
        ]);
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);

        $this->setBalanceZero();
        $this->artisan('ledger:regenerate-accounts-balance', [
            '-L' => $this->ledger->id,
        ]);
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);

        $this->setBalanceZero();
        $this->artisan('ledger:regenerate-accounts-balance', [
            '--ledger' => $this->ledger->id,
        ]);
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);

        $this->setBalanceZero();
        $this->artisan('ledger:regenerate-accounts-balance', [
            '-L' => $this->ledger->id,
            '-A' => $this->accountUSD->id,
        ]);
        $this->accountUSD->refresh();
        $this->assertEquals($balance, $this->accountUSD->balance);
    }

    /** @test */
    public function exception_if_not_valid_leger_type_create()
    {
        $this->prepareDatabase();
        $this->expectException(LaravelLedgerException::class);

        factory(LedgerAction::class, 4)->create([
            'account_id' => $this->accountUSD->id,
            'type' => 'not_exists',
        ]);
    }

    /** @test */
    public function exception_if_not_valid_leger_type_delete()
    {
        $this->prepareDatabase();

        $ledgerAction = factory(LedgerAction::class)->create([
            'account_id' => $this->accountUSD->id,
        ]);

        $updater = new AccountBalanceUpdater($ledgerAction);

        $ledgerAction->type = 'not_exists';

        $this->expectException(LaravelLedgerException::class);
        $updater->onDeleted();
    }

    /** @test */
    public function exception_if_not_valid_balance_updaters()
    {
        $this->prepareDatabase();

        $ledgerAction = factory(LedgerAction::class)->create([
            'account_id' => $this->accountUSD->id,
        ]);

        $this->expectException(LaravelLedgerException::class);
        $ledgerAction->removeFromBalance([RegenerateBalanceCommand::class]);
    }
}
