<?php

namespace App\Models;

use App\Support\Sheets\SheetModel;

class ReplenishmentItem extends SheetModel
{
    protected array $fillable = [
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

    protected array $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function report(): ?ReplenishmentReport
    {
        return ReplenishmentReport::find($this->replenishment_report_id);
    }

    public function expense(): ?Expense
    {
        return $this->expense_id ? Expense::find($this->expense_id) : null;
    }
}