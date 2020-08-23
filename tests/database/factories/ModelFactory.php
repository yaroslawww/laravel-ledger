<?php

use \Faker\Generator;

/* @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(\LaravelLedger\Models\LedgerBook::class, function (Generator $faker) {
    return [
        'name' => $faker->word,
        'note' => $faker->sentence,
        'owner_id' => null,
        'owner_type' => null,
    ];
});

/* @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(\LaravelLedger\Models\LedgerAccount::class, function (Generator $faker) {
    return [
        'ledger_id' => \LaravelLedger\Models\LedgerBook::inRandomOrder()->first()->id,
        'name' => $faker->word,
        'note' => $faker->sentence,
    ];
});

/* @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(\LaravelLedger\Models\LedgerAction::class, function (Generator $faker) {
    return [
        'account_id' => \LaravelLedger\Models\LedgerAccount::inRandomOrder()->first()->id,
        'type' => (string) (rand(0, 1) ? \LaravelLedger\Enums\LedgerActionType::expense() : \LaravelLedger\Enums\LedgerActionType::income()),
        'sum' => $faker->numberBetween(1, 999),
        'memo' => $faker->sentence,
        'datetime' => now(),
    ];
});
