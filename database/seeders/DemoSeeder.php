<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Credits\CreditLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo users with starting balances so a fresh deployment has something to
 * look at. Deliberately NO fake generations: history should only ever contain
 * records that went through the real provider pipeline — the admin report's
 * reconciliation against GET /v1/usage must not be poisoned with numbers the
 * provider never saw. Generate a few images right after seeding to populate
 * the history (locally the sim does this in seconds; keywords like [fail]
 * and [duplicate] make good demo material).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $ledger = app(CreditLedger::class);

        $admin = User::create([
            'name' => 'Ada Operator',
            'email' => 'admin@promptedit.test',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $demo = User::create([
            'name' => 'Dana Demo',
            'email' => 'demo@promptedit.test',
            'password' => Hash::make('password'),
        ]);

        $sam = User::create([
            'name' => 'Sam Second',
            'email' => 'sam@promptedit.test',
            'password' => Hash::make('password'),
        ]);

        // Fake purchases start every balance (see the ledger for the audit trail).
        $ledger->purchase($demo, 10_000_000, 'Buy credits ($10.00)');
        $ledger->purchase($sam, 5_000_000, 'Buy credits ($5.00)');
        $ledger->purchase($admin, 25_000_000, 'Buy credits ($25.00)');

        $this->command?->info('Seeded 3 users with credit balances. History fills as generations run.');
    }
}
