<?php

namespace Database\Seeders;

use App\Models\PettyCashFund;
use Illuminate\Database\Seeder;

class PettyCashSeeder extends Seeder
{
    public function run(): void
    {
        if (PettyCashFund::count() === 0) {
            PettyCashFund::create([
                'total_amount' => config('pettycash.fund_target', 30000.00),
                'current_balance' => config('pettycash.fund_target', 30000.00),
                'status' => 'active',
            ]);
        }
    }
}
