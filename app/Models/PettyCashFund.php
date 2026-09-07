<?php

namespace App\Models;

use App\Support\Sheets\SheetModel;
use App\Support\Sheets\SheetQuery;

class PettyCashFund extends SheetModel
{
    protected array $fillable = [
        'total_amount',
        'current_balance',
        'status',
    ];

    protected array $casts = [
        'total_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function expenses(): SheetQuery
    {
        return Expense::query()->where('fund_id', $this->id);
    }

    public function replenishmentRequests(): SheetQuery
    {
        return ReplenishmentRequest::query()->where('fund_id', $this->id);
    }

    public function getThresholdAttribute(): float
    {
        return (float) $this->total_amount * 0.20;
    }

    public function getBalancePercentageAttribute(): float
    {
        if ((float) $this->total_amount <= 0) {
            return 0;
        }

        return ((float) $this->current_balance / (float) $this->total_amount) * 100;
    }

    public function isBelowThreshold(): bool
    {
        return $this->getTotalExpensesAttribute() >= $this->threshold;
    }

    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->current_balance;
    }

    public function getReplenishAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->current_balance;
    }
}