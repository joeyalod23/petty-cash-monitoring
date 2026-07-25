<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentRequest extends Model
{
    protected $fillable = [
        'fund_id',
        'requested_amount',
        'status',
        'triggered_by',
    ];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'fund_id');
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function disburse(): void
    {
        $this->update(['status' => 'disbursed']);

        $fund = $this->fund;
        $fund->current_balance = 30000.00;
        $fund->status = 'active';
        $fund->save();
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
