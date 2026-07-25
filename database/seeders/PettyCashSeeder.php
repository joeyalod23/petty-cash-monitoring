<?php

namespace Database\Seeders;

use App\Models\PettyCashFund;
use App\Models\Expense;
use Illuminate\Database\Seeder;

class PettyCashSeeder extends Seeder
{
    public function run(): void
    {
        $fund = PettyCashFund::create([
            'total_amount' => 5000.00,
            'current_balance' => 5000.00,
            'status' => 'active',
        ]);

        $expenses = [
            ['payee' => 'National Book Store', 'category' => 'Supplies', 'particular' => 'Bond paper and pens for printing documents', 'amount' => 350.00, 'receipt_number' => 'REC-001', 'expense_date' => '2026-07-15'],
            ['payee' => 'Grab Philippines', 'category' => 'Transportation', 'particular' => 'Grab to client site inspection', 'amount' => 480.00, 'receipt_number' => 'REC-002', 'expense_date' => '2026-07-16'],
            ['payee' => 'Jollibee', 'category' => 'Meals', 'particular' => 'Team lunch during site visit', 'amount' => 1250.00, 'receipt_number' => 'REC-003', 'expense_date' => '2026-07-17'],
            ['payee' => 'Globe Telecom', 'category' => 'Communication', 'particular' => 'Monthly internet and load allowance', 'amount' => 500.00, 'receipt_number' => 'REC-004', 'expense_date' => '2026-07-18'],
            ['payee' => 'SM Supplies', 'category' => 'Office', 'particular' => 'Printer ink and folders', 'amount' => 720.00, 'receipt_number' => 'REC-005', 'expense_date' => '2026-07-19'],
        ];

        foreach ($expenses as $exp) {
            Expense::create(array_merge($exp, ['fund_id' => $fund->id]));
            $fund->current_balance -= $exp['amount'];
        }

        $fund->save();
    }
}
