<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\PettyCashFund;
use App\Models\ReplenishmentRequest;
use Illuminate\Support\Facades\DB;

class PettyCashService
{
    public function recordExpense(PettyCashFund $fund, array $data): array
    {
        return DB::transaction(function () use ($fund, $data) {
            $freshFund = PettyCashFund::lockForUpdate()->findOrFail($fund->id);

            $expenseAmount = round((float) $data['amount'], 2);

            if ((float) $freshFund->current_balance < $expenseAmount) {
                throw new \RuntimeException('Insufficient Petty Cash Balance');
            }

            $newBalance = round((float) $freshFund->current_balance - $expenseAmount, 2);
            $threshold = round((float) $freshFund->total_amount * 0.30, 2);
            $shouldTrigger = $newBalance <= $threshold;

            $freshFund->current_balance = $newBalance;
            if ($shouldTrigger && $freshFund->status !== 'replenishment_pending') {
                $freshFund->status = 'low_balance';
            }
            $freshFund->save();

            $expense = Expense::create([
                'fund_id' => $freshFund->id,
                'payee' => $data['payee'],
                'category' => $data['category'],
                'amount' => $expenseAmount,
                'receipt_number' => $data['receipt_number'] ?? null,
                'expense_date' => $data['expense_date'],
            ]);

            if ($shouldTrigger) {
                $replenishAmount = round((float) $freshFund->total_amount - $newBalance, 2);

                ReplenishmentRequest::create([
                    'fund_id' => $freshFund->id,
                    'requested_amount' => $replenishAmount,
                    'status' => 'pending',
                    'triggered_by' => 'System Auto-Trigger (30% Balance Alert)',
                ]);
            }

            return [
                'expense' => $expense,
                'current_balance' => $newBalance,
                'alert_triggered' => $shouldTrigger,
                'threshold' => $threshold,
            ];
        });
    }

    public function createFund(float $totalAmount): PettyCashFund
    {
        return PettyCashFund::create([
            'total_amount' => $totalAmount,
            'current_balance' => $totalAmount,
            'status' => 'active',
        ]);
    }

    public function approveReplenishment(ReplenishmentRequest $request): void
    {
        $request->approve();
    }

    public function disburseReplenishment(ReplenishmentRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $request->disburse();

            $pendingRequest = ReplenishmentRequest::where('fund_id', $request->fund_id)
                ->where('id', '!=', $request->id)
                ->where('status', 'pending')
                ->first();

            if (!$pendingRequest) {
                $request->fund->status = 'active';
                $request->fund->save();
            }
        });
    }

    public function rejectReplenishment(ReplenishmentRequest $request): void
    {
        $request->reject();
    }
}
