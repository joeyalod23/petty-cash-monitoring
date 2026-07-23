<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReplenishmentReport extends Model
{
    protected $fillable = [
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

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'report_date' => 'date',
        'cash_received' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReplenishmentItem::class, 'replenishment_report_id');
    }

    public function getTotalLiquidatedAttribute(): float
    {
        return (float) $this->items()->sum('amount');
    }

    public function getForReturnAttribute(): float
    {
        return (float) $this->cash_received - $this->total_liquidated;
    }
}
