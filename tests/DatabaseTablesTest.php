<?php

namespace LaravelLedger\Tests;

use LaravelLedger\Enums\LedgerActionType;
use LaravelLedger\Models\LedgerAccount;
use LaravelLedger\Models\LedgerAction;
use LaravelLedger\Models\LedgerBook;

class DatabaseTablesTest extends TestCase
{

    /** @test */
    public function can_not_create_same_ledger_name()
    {
        factory(LedgerBook::class)->create([
            'name' => 'TestLed',
        ]);

        $this->expectException(\PDOException::class);
        factory(LedgerBook::class)->create([
            'name' => 'TestLed',
        ]);
    }

    /** @test */
    public function tables_exists()
    {
        $this->ledgersTableExists();
        $this->accountsTableExists();
        $this->acctionsTableExists();
    }

    public function ledgersTableExists()
    {
        $name = 'TestNow';
        $note = 'TestNote';
        $ownerId = 1;
        $ownerType = LedgerBook::class;
        $meta = json_encode(['tag' => 'test']);

        $ledger = factory(LedgerBook::class)->create([
            'name' => $name,
            'note' => $note,
            'owner_id' => $ownerId,
            'owner_type' => $ownerType,
            'meta' => $meta,
        ]);

        $this->assertEquals($name, $ledger->name);

        $ledgerNew = LedgerBook::find($ledger->id);

        $this->assertNotNull($ledgerNew);

        $this->assertEquals($name, $ledgerNew->name);
        $this->assertEquals($note, $ledgerNew->note);
        $this->assertEquals($ownerId, $ledgerNew->owner_id);
        $this->assertEquals($ownerType, $ledgerNew->owner_type);
        $this->assertEquals($meta, $ledgerNew->meta);
    }


    public function accountsTableExists()
    {
        $ledger = \LaravelLedger\Models\LedgerBook::inRandomOrder()->first();
        $this->assertNotNull($ledger);

        $name = 'TestNow';
        $note = 'TestNote';
        $balance = 100;
        $currency = 'GBP';
        $meta = json_encode(['tag' => 'test']);

        factory(LedgerAccount::class, 3)->create([
            'ledger_id' => $ledger->id,
        ]);

        $account = factory(LedgerAccount::class)->create([
            'ledger_id' => $ledger->id,
            'name' => $name,
            'note' => $note,
            'balance' => $balance,
            'currency' => $currency,
            'meta' => $meta,
        ]);

        $this->assertEquals($name, $account->name);

        $accountNew = LedgerAccount::find($account->id);

        $this->assertNotNull($accountNew);

        $this->assertEquals($ledger->id, $accountNew->ledger_id);
        $this->assertEquals($name, $accountNew->name);
        $this->assertEquals($note, $accountNew->note);
        $this->assertEquals($balance, $accountNew->balance);
        $this->assertEquals($currency, $accountNew->currency);
        $this->assertEquals($meta, $accountNew->meta);

        $this->assertEquals($accountNew->ledger->id, $ledger->id);
        $this->assertCount(4, $accountNew->ledger->accounts);
    }


    public function acctionsTableExists()
    {
        $account = \LaravelLedger\Models\LedgerAccount::inRandomOrder()->first();
        $this->assertNotNull($account);

        $memo = 'TestNow';
        $type = (string) LedgerActionType::income();
        $sum = 100;
        $meta = json_encode(['tag' => 'test']);

        factory(LedgerAction::class, 5)->create([
            'account_id' => $account->id,
        ]);

        $action = factory(LedgerAction::class)->create([
            'account_id' => $account->id,
            'memo' => $memo,
            'type' => $type,
            'sum' => $sum,
            'meta' => $meta,
        ]);


        $this->assertEquals('string', $action->getKeyType());
        $this->assertEquals($sum, $action->sum);

        $actionNew = LedgerAction::find($action->uuid);

        $this->assertNotNull($actionNew);

        $this->assertEquals($account->id, $actionNew->account_id);
        $this->assertEquals($memo, $actionNew->memo);
        $this->assertEquals($type, $actionNew->type);
        $this->assertEquals($sum, $actionNew->sum);
        $this->assertEquals($meta, $actionNew->meta);

        $this->assertEquals($actionNew->account->id, $account->id);
        $this->assertEquals($actionNew->account->ledger->id, $account->ledger->id);
        $this->assertCount(6, $actionNew->account->actions);
        $this->assertCount(6, $account->ledger->actions);
    }
}
