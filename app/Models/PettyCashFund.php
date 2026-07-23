<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashFund extends Model
{
    protected $fillable = [
        'total_amount',
        'current_balance',
        'status',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'fund_id');
    }

    public function replenishmentRequests(): HasMany
    {
        return $this->hasMany(ReplenishmentRequest::class, 'fund_id');
    }

    public function getThresholdAttribute(): float
    {
        return (float) $this->total_amount * 0.30;
    }

    public function getBalancePercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return ((float) $this->current_balance / (float) $this->total_amount) * 100;
    }

    public function isBelowThreshold(): bool
    {
        $totalExpenses = (float) $this->total_amount - (float) $this->current_balance;
        return $totalExpenses >= $this->threshold;
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
