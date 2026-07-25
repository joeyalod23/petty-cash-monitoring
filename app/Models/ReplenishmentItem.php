<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentItem extends Model
{
    protected $fillable = [
        'replenishment_report_id',
        'expense_id',
        'expense_date',
        'voucher_no',
        'reference_no',
        'payee',
        'cost_code',
        'particulars',
        'amount',
        'group_key',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentReport::class, 'replenishment_report_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentReport::class, 'replenishment_report_id');
    }
}
