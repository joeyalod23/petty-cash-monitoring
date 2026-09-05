<?php

namespace App\Models;

use App\Services\PettyCashService;
use App\Support\Sheets\SheetModel;

class ReplenishmentRequest extends SheetModel
{
    protected array $fillable = [
        'fund_id',
        'requested_amount',
        'status',
        'triggered_by',
    ];

    protected array $casts = [
        'requested_amount' => 'decimal:2',
    ];

    public function fund(): ?PettyCashFund
    {
        return PettyCashFund::find($this->fund_id);
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function disburse(): void
    {
        $this->update(['status' => 'disbursed']);

        $fund = $this->fund;

        if ($fund) {
            $fund->current_balance = PettyCashService::fundTarget();
            $fund->status = 'active';
            $fund->save();
        }
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}