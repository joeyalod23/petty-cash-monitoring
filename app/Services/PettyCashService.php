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
            $expenseAmount = (float) $data['amount'];

            if ((float) $fund->current_balance < $expenseAmount) {
                throw new \RuntimeException('Insufficient Petty Cash Balance');
            }

            $expense = Expense::create([
                'fund_id' => $fund->id,
                'payee' => $data['payee'],
                'category' => $data['category'],
                'amount' => $expenseAmount,
                'receipt_number' => $data['receipt_number'] ?? null,
                'expense_date' => $data['expense_date'],
            ]);

            $fund->current_balance = (float) $fund->current_balance - $expenseAmount;

            $threshold = (float) $fund->total_amount * 0.30;
            $shouldTrigger = $fund->current_balance <= $threshold;

            if ($shouldTrigger && $fund->status !== 'replenishment_pending') {
                $fund->status = 'low_balance';

                $replenishAmount = (float) $fund->total_amount - (float) $fund->current_balance;

                ReplenishmentRequest::create([
                    'fund_id' => $fund->id,
                    'requested_amount' => $replenishAmount,
                    'status' => 'pending',
                    'triggered_by' => 'System Auto-Trigger (30% Balance Alert)',
                ]);
            }

            $fund->save();

            return [
                'expense' => $expense,
                'current_balance' => $fund->current_balance,
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
