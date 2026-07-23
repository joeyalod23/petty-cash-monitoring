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

        DB::beginTransaction();

        try {
            $row = DB::selectOne(
                'SELECT id, total_amount, current_balance, status FROM petty_cash_funds WHERE id = ?',
                [$fund->id]
            );

            if (!$row) {
                throw new \RuntimeException('Fund not found.');
            }

            if ((float) $row->current_balance < (float) $expenseAmount) {
                DB::rollBack();
                throw new \RuntimeException('Insufficient Petty Cash Balance');
            }

            $newBalance = number_format((float) $row->current_balance - (float) $expenseAmount, 2, '.', '');
            $threshold = number_format((float) $row->total_amount * 0.30, 2, '.', '');
            $shouldTrigger = (float) $newBalance <= (float) $threshold;

            $newStatus = $row->status;
            if ($shouldTrigger && $row->status !== 'replenishment_pending') {
                $newStatus = 'low_balance';
            }

            DB::update(
                'UPDATE petty_cash_funds SET current_balance = ?, status = ?, updated_at = NOW() WHERE id = ?',
                [$newBalance, $newStatus, $row->id]
            );

            $expenseId = DB::insertGetId(
                'INSERT INTO expenses (fund_id, payee, category, amount, receipt_number, expense_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $row->id,
                    $data['payee'],
                    $data['category'],
                    $expenseAmount,
                    $data['receipt_number'] ?? null,
                    $data['expense_date'],
                ]
            );

            if ($shouldTrigger) {
                $replenishAmount = number_format((float) $row->total_amount - (float) $newBalance, 2, '.', '');

                DB::insert(
                    'INSERT INTO replenishment_requests (fund_id, requested_amount, status, triggered_by, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                    [
                        $row->id,
                        $replenishAmount,
                        'pending',
                        'System Auto-Trigger (30% Balance Alert)',
                    ]
                );
            }

            DB::commit();

            return [
                'expense' => Expense::find($expenseId),
                'current_balance' => (float) $newBalance,
                'alert_triggered' => $shouldTrigger,
                'threshold' => (float) $threshold,
            ];
        } catch (\RuntimeException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        DB::beginTransaction();

        try {
            $request->disburse();

            $pendingRequest = ReplenishmentRequest::where('fund_id', $request->fund_id)
                ->where('id', '!=', $request->id)
                ->where('status', 'pending')
                ->first();

            if (!$pendingRequest) {
                $request->fund->status = 'active';
                $request->fund->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectReplenishment(ReplenishmentRequest $request): void
    {
        $request->reject();
    }
}
