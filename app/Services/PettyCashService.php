<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\PettyCashFund;
use App\Models\ReplenishmentRequest;

class PettyCashService
{
    public static function fundTarget(): float
    {
        return (float) config('pettycash.fund_target', 30000.00);
    }

    public static function lowBalanceThreshold(): float
    {
        return (float) config('pettycash.low_balance_threshold', 0.30);
    }

    public function recordExpense(PettyCashFund $fund, array $data): array
    {
        return $this->withRowLock($fund->id, function () use ($fund, $data) {
            $fresh = PettyCashFund::find($fund->id);

            if (!$fresh) {
                throw new \RuntimeException('Fund not found.');
            }

            $expenseAmount = (float) $data['amount'];
            $fundTarget = self::fundTarget();
            $currentBalance = (float) $fresh->current_balance;

            if ($currentBalance < $expenseAmount) {
                throw new \RuntimeException('Insufficient Petty Cash Balance');
            }

            $newBalance = $currentBalance - $expenseAmount;
            $threshold = $fundTarget * 0.30;
            $totalExpenses = $fundTarget - $newBalance;
            $shouldTrigger = $totalExpenses >= $threshold;

            $fresh->current_balance = $this->round($newBalance);
            if ($shouldTrigger && $fresh->status !== 'replenishment_pending') {
                $fresh->status = 'low_balance';
            }
            $fresh->save();

            $expense = Expense::create([
                'fund_id' => $fresh->id,
                'payee' => $data['payee'],
                'category' => $data['category'],
                'particular' => $data['particular'] ?? null,
                'cost_code' => $data['cost_code'],
                'amount' => $this->round($expenseAmount),
                'receipt_number' => $data['receipt_number'] ?: null,
                'expense_date' => $data['expense_date'],
                'status' => 'open',
            ]);

            if ($shouldTrigger) {
                $this->autoTriggerIfPending($fresh, $newBalance);
            }

            return [
                'expense' => $expense,
                'current_balance' => $newBalance,
                'alert_triggered' => $shouldTrigger,
                'threshold' => $threshold,
                'total_expenses' => $totalExpenses,
            ];
        });
    }

    public function createFund(float $totalAmount): PettyCashFund
    {
        $existingFund = PettyCashFund::first();

        if ($existingFund) {
            $existingFund->current_balance = $this->round((float) $existingFund->current_balance + $totalAmount);
            $existingFund->save();

            return $existingFund;
        }

        return PettyCashFund::create([
            'total_amount' => self::fundTarget(),
            'current_balance' => $this->round($totalAmount),
            'status' => 'active',
        ]);
    }

    public function updateFund(PettyCashFund $fund, float $totalAmount): void
    {
        $fund->update([
            'total_amount' => $this->round($totalAmount),
            'current_balance' => $this->round($totalAmount),
        ]);
    }

    public function deleteFund(PettyCashFund $fund): void
    {
        $fund->delete();
    }

    public function updateExpense(Expense $expense, array $data): void
    {
        $this->withRowLock($expense->fund_id, function () use ($expense, $data) {
            $oldAmount = (float) $expense->amount;
            $newAmount = (float) $data['amount'];
            $diff = $newAmount - $oldAmount;

            $expense->update([
                'payee' => $data['payee'],
                'category' => $data['category'],
                'particular' => $data['particular'] ?? null,
                'cost_code' => $data['cost_code'],
                'amount' => $this->round($newAmount),
                'receipt_number' => $data['receipt_number'] ?: null,
                'expense_date' => $data['expense_date'],
            ]);

            if (abs($diff) < 0.005) {
                return;
            }

            $fresh = PettyCashFund::find($expense->fund_id);

            if (!$fresh) {
                return;
            }

            $fundTarget = self::fundTarget();
            $newBalance = (float) $fresh->current_balance - $diff;
            $threshold = $fundTarget * 0.30;
            $totalExpenses = $fundTarget - $newBalance;
            $shouldTrigger = $totalExpenses >= $threshold;

            $fresh->current_balance = $this->round($newBalance);
            if ($shouldTrigger && $fresh->status !== 'replenishment_pending') {
                $fresh->status = 'low_balance';
            }
            $fresh->save();

            if ($shouldTrigger) {
                $this->autoTriggerIfPending($fresh, $newBalance);
            }
        });
    }

    public function deleteExpense(Expense $expense): void
    {
        $this->withRowLock($expense->fund_id, function () use ($expense) {
            $amount = (float) $expense->amount;
            $fundId = $expense->fund_id;

            $expense->delete();

            $fresh = PettyCashFund::find($fundId);

            if ($fresh) {
                $fresh->current_balance = $this->round((float) $fresh->current_balance + $amount);
                $fresh->save();
            }
        });
    }

    public function createReplenishment(int $fundId, float $amount): void
    {
        ReplenishmentRequest::create([
            'fund_id' => $fundId,
            'requested_amount' => $this->round($amount),
            'status' => 'pending',
            'triggered_by' => 'Manual Request',
        ]);
    }

    public function updateReplenishment(ReplenishmentRequest $request, array $data): void
    {
        $request->update([
            'fund_id' => $data['fund_id'],
            'requested_amount' => $this->round((float) $data['requested_amount']),
            'status' => $data['status'],
        ]);
    }

    public function deleteReplenishment(ReplenishmentRequest $request): void
    {
        $request->delete();
    }

    public function approveReplenishment(ReplenishmentRequest $request): void
    {
        $request->approve();
    }

    public function disburseReplenishment(ReplenishmentRequest $request): void
    {
        $request->disburse();

        $pendingRequest = ReplenishmentRequest::query()
            ->where('fund_id', $request->fund_id)
            ->where('status', 'pending')
            ->first();

        if (!$pendingRequest && $request->fund) {
            $request->fund->current_balance = self::fundTarget();
            $request->fund->status = 'active';
            $request->fund->save();
        }
    }

    public function rejectReplenishment(ReplenishmentRequest $request): void
    {
        $request->reject();
    }

    private function autoTriggerIfPending(PettyCashFund $fresh, float $newBalance): void
    {
        $hasPending = ReplenishmentRequest::query()
            ->where('fund_id', $fresh->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return;
        }

        ReplenishmentRequest::create([
            'fund_id' => $fresh->id,
            'requested_amount' => $this->round((float) $fresh->total_amount - $newBalance),
            'status' => 'pending',
            'triggered_by' => 'System Auto-Trigger (30% Total Expense Alert - To Liquidate & Replenish)',
        ]);
    }

    private function withRowLock(int $fundId, callable $callback): mixed
    {
        $lockFile = storage_path('framework/locks/fund-' . $fundId . '.lock');

        if (!is_dir(dirname($lockFile))) {
            @mkdir(dirname($lockFile), 0777, true);
        }

        $handle = fopen($lockFile, 'c');

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Could not acquire fund lock.');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function round(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}