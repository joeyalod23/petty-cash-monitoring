<?php

namespace App\Models;

use App\Support\Sheets\SheetModel;
use App\Support\Sheets\SheetQuery;

class ReplenishmentReport extends SheetModel
{
    protected array $fillable = [
        'project_name',
        'location',
        'subject',
        'period_start',
        'period_end',
        'report_date',
        'cash_received',
        'prepared_by',
        'reviewed_by',
        'verified_by',
    ];

    protected array $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'report_date' => 'date',
        'cash_received' => 'decimal:2',
    ];

    public function items(): SheetQuery
    {
        return ReplenishmentItem::query()->where('replenishment_report_id', $this->id);
    }

    public function getTotalLiquidatedAttribute(): float
    {
        return $this->items()->sum('amount');
    }

    public function getForReturnAttribute(): float
    {
        return (float) $this->cash_received - $this->total_liquidated;
    }
}