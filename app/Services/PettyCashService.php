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
        $expenseAmount = number_format((float) $data['amount'], 2, '.', '');

        $row = DB::selectOne(
            'SELECT id, total_amount, current_balance, status FROM petty_cash_funds WHERE id = ?',
            [$fund->id]
        );

        if (!$row) {
            throw new \RuntimeException('Fund not found.');
        }

        if ((float) $row->current_balance < (float) $expenseAmount) {
            throw new \RuntimeException('Insufficient Petty Cash Balance');
        }

        $newBalance = number_format((float) $row->current_balance - (float) $expenseAmount, 2, '.', '');
        $threshold = number_format((float) $row->total_amount * 0.30, 2, '.', '');
        $totalExpenses = number_format((float) $row->total_amount - (float) $newBalance, 2, '.', '');
        $shouldTrigger = (float) $totalExpenses >= (float) $threshold;

        $newStatus = $row->status;
        if ($shouldTrigger && $row->status !== 'replenishment_pending') {
            $newStatus = 'low_balance';
        }

        DB::update(
            'UPDATE petty_cash_funds SET current_balance = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [$newBalance, $newStatus, $row->id]
        );

        $expenseId = DB::table('expenses')->insertGetId([
            'fund_id' => $row->id,
            'payee' => $data['payee'],
            'category' => $data['category'],
            'amount' => $expenseAmount,
            'receipt_number' => $data['receipt_number'] ?: null,
            'expense_date' => $data['expense_date'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($shouldTrigger) {
            $existingPending = DB::selectOne(
                'SELECT id FROM replenishment_requests WHERE fund_id = ? AND status = ?',
                [$row->id, 'pending']
            );

            if (!$existingPending) {
                $replenishAmount = number_format((float) $row->total_amount - (float) $newBalance, 2, '.', '');

                DB::insert(
                    'INSERT INTO replenishment_requests (fund_id, requested_amount, status, triggered_by, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                    [
                        $row->id,
                        $replenishAmount,
                        'pending',
                        'System Auto-Trigger (30% Total Expense Alert - To Liquidate & Replenish)',
                    ]
                );
            }
        }

        return [
            'expense' => Expense::find($expenseId),
            'current_balance' => (float) $newBalance,
            'alert_triggered' => $shouldTrigger,
            'threshold' => (float) $threshold,
            'total_expenses' => (float) $totalExpenses,
        ];
    }

    public function createFund(float $totalAmount): PettyCashFund
    {
        return PettyCashFund::create([
            'total_amount' => $totalAmount,
            'current_balance' => $totalAmount,
            'status' => 'active',
        ]);
    }

    public function updateFund(PettyCashFund $fund, float $totalAmount): void
    {
        $fund->update([
            'total_amount' => $totalAmount,
        ]);
    }

    public function deleteFund(PettyCashFund $fund): void
    {
        $fund->delete();
    }

    public function updateExpense(Expense $expense, array $data): void
    {
        $oldAmount = (float) $expense->amount;
        $newAmount = (float) $data['amount'];
        $diff = $newAmount - $oldAmount;

        DB::update(
            'UPDATE expenses SET payee = ?, category = ?, amount = ?, receipt_number = ?, expense_date = ?, updated_at = NOW() WHERE id = ?',
            [
                $data['payee'],
                $data['category'],
                number_format($newAmount, 2, '.', ''),
                $data['receipt_number'] ?: null,
                $data['expense_date'],
                $expense->id,
            ]
        );

        if ($diff != 0) {
            $row = DB::selectOne(
                'SELECT id, total_amount, current_balance, status FROM petty_cash_funds WHERE id = ?',
                [$expense->fund_id]
            );

            if ($row) {
                $newBalance = number_format((float) $row->current_balance - $diff, 2, '.', '');
                $threshold = number_format((float) $row->total_amount * 0.30, 2, '.', '');
                $totalExpenses = number_format((float) $row->total_amount - (float) $newBalance, 2, '.', '');
                $shouldTrigger = (float) $totalExpenses >= (float) $threshold;

                $newStatus = $row->status;
                if ($shouldTrigger && $row->status !== 'replenishment_pending') {
                    $newStatus = 'low_balance';
                }

                DB::update(
                    'UPDATE petty_cash_funds SET current_balance = ?, status = ?, updated_at = NOW() WHERE id = ?',
                    [$newBalance, $newStatus, $row->id]
                );

                if ($shouldTrigger) {
                    $existingPending = DB::selectOne(
                        'SELECT id FROM replenishment_requests WHERE fund_id = ? AND status = ?',
                        [$row->id, 'pending']
                    );

                    if (!$existingPending) {
                        $replenishAmount = number_format((float) $row->total_amount - (float) $newBalance, 2, '.', '');

                        DB::insert(
                            'INSERT INTO replenishment_requests (fund_id, requested_amount, status, triggered_by, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                            [
                                $row->id,
                                $replenishAmount,
                                'pending',
                                'System Auto-Trigger (30% Total Expense Alert - To Liquidate & Replenish)',
                            ]
                        );
                    }
                }
            }
        }
    }

    public function deleteExpense(Expense $expense): void
    {
        $amount = (float) $expense->amount;

        DB::delete('DELETE FROM expenses WHERE id = ?', [$expense->id]);

        $row = DB::selectOne(
            'SELECT id, total_amount, current_balance, status FROM petty_cash_funds WHERE id = ?',
            [$expense->fund_id]
        );

        if ($row) {
            $newBalance = number_format((float) $row->current_balance + $amount, 2, '.', '');

            DB::update(
                'UPDATE petty_cash_funds SET current_balance = ?, updated_at = NOW() WHERE id = ?',
                [$newBalance, $row->id]
            );
        }
    }

    public function createReplenishment(int $fundId, float $amount): void
    {
        DB::insert(
            'INSERT INTO replenishment_requests (fund_id, requested_amount, status, triggered_by, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [$fundId, number_format($amount, 2, '.', ''), 'pending', 'Manual Request']
        );
    }

    public function updateReplenishment(ReplenishmentRequest $request, array $data): void
    {
        $request->update([
            'fund_id' => $data['fund_id'],
            'requested_amount' => $data['requested_amount'],
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

        $pendingRequest = ReplenishmentRequest::where('fund_id', $request->fund_id)
            ->where('id', '!=', $request->id)
            ->where('status', 'pending')
            ->first();

        if (!$pendingRequest) {
            $request->fund->status = 'active';
            $request->fund->save();
        }
    }

    public function rejectReplenishment(ReplenishmentRequest $request): void
    {
        $request->reject();
    }
}
