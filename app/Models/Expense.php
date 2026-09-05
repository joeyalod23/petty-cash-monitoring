<?php

namespace App\Models;

use App\Support\Sheets\SheetModel;

class Expense extends SheetModel
{
    protected array $fillable = [
        'fund_id',
        'payee',
        'category',
        'particular',
        'cost_code',
        'amount',
        'receipt_number',
        'expense_date',
        'status',
    ];

    protected array $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function fund(): ?PettyCashFund
    {
        return PettyCashFund::find($this->fund_id);
    }
}