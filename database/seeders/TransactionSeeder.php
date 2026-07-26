<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('name', 'Test Tenant')->first();

        if ($tenant === null) {
            return;
        }

        app(TenantContext::class)->setTenantId($tenant->id);

        $accounts = $tenant->accounts()->get()->keyBy('name');

        if ($accounts->isEmpty()) {
            return;
        }

        $months = [
            now()->startOfMonth(),
            now()->startOfMonth()->addMonthNoOverflow(),
            now()->startOfMonth()->addMonthsNoOverflow(2),
        ];

        // 10 transactions across 3 consecutive months (current, +1, +2).
        $rows = [
            // Current month.
            ['month' => 0, 'day' => 5, 'movement' => 'income', 'amount' => 5000, 'description' => 'Salário', 'account' => 'Conta Corrente', 'paid' => true],
            ['month' => 0, 'day' => 5, 'movement' => 'expense', 'amount' => 1800, 'description' => 'Aluguel', 'account' => 'Conta Corrente', 'paid' => true],
            ['month' => 0, 'day' => 12, 'movement' => 'expense', 'amount' => 650.50, 'description' => 'Mercado', 'account' => 'Conta Corrente', 'paid' => true],
            ['month' => 0, 'day' => 20, 'movement' => 'expense', 'amount' => 120, 'description' => 'Internet', 'account' => 'Conta Corrente', 'paid' => false],

            // Next month.
            ['month' => 1, 'day' => 5, 'movement' => 'income', 'amount' => 5000, 'description' => 'Salário', 'account' => 'Conta Corrente', 'paid' => false],
            ['month' => 1, 'day' => 10, 'movement' => 'expense', 'amount' => 240.90, 'description' => 'Restaurante', 'account' => 'Conta Corrente', 'paid' => false],
            ['month' => 1, 'day' => 15, 'movement' => 'income', 'amount' => 800, 'description' => 'Freelance', 'account' => 'Conta Dólar', 'paid' => false],

            // Month after next.
            ['month' => 2, 'day' => 5, 'movement' => 'income', 'amount' => 5000, 'description' => 'Salário', 'account' => 'Conta Corrente', 'paid' => false],
            ['month' => 2, 'day' => 8, 'movement' => 'expense', 'amount' => 3200, 'description' => 'Notebook', 'account' => 'Conta Corrente', 'paid' => false],
            ['month' => 2, 'day' => 18, 'movement' => 'expense', 'amount' => 15, 'description' => 'Assinatura', 'account' => 'Carteira Tether', 'paid' => false],
        ];

        foreach ($rows as $row) {
            $account = $accounts->get($row['account']);

            if ($account === null) {
                continue;
            }

            $date = $months[$row['month']]->addDays($row['day'] - 1);

            Transaction::query()->create([
                'tenant_id' => $tenant->id,
                'account_id' => $account->id,
                'currency_id' => $account->currency_id,
                'movement_type' => $row['movement'],
                'type' => 'unique',
                'effective_date' => $date->toDateString(),
                'paid_at' => $row['paid'] ? $date->toDateString() : null,
                'amount' => $row['amount'],
                'description' => $row['description'],
            ]);
        }
    }
}
