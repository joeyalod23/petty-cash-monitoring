<?php

namespace App\Rules;

use App\Support\Sheets\SheetDatabase;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SheetExists implements ValidationRule
{
    public function __construct(private string $table) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!resolve(SheetDatabase::class)->find($this->table, $value)) {
            $fail('The selected :attribute is invalid.');
        }
    }
}