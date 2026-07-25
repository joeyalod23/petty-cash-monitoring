@extends('layouts.app')
@php
    $totalExpenses = $fund->total_amount - $fund->current_balance;
    $expensePct = $fund->total_amount > 0 ? ($totalExpenses / $fund->total_amount) * 100 : 0;
    $fillClass = $expensePct > 50 ? 'red' : ($expensePct > 30 ? 'yellow' : 'green');
@endphp

@section('title', 'Log Expense - Fund #' . $fund->id)
@section('page-title', 'Log Expense')

@section('content')
<a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
</a>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    {{-- FORM --}}
    <div class="card">
        <div class="card-header">
            <h3>Expense Details</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('fund.expenses.store', $fund) }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="payee">Payee Name</label>
                        <input type="text" name="payee" id="payee" class="form-control" value="{{ old('payee') }}" required placeholder="Who was paid?">
                        @error('payee')
                            <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" class="form-control" required>
                            <option value="">Select category</option>
                            @foreach(['Supplies','Transportation','Meals','Communication','Office','Maintenance','Other'] as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="particular">Particular <span style="color:var(--text-muted);text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="particular" id="particular" class="form-control" value="{{ old('particular') }}" placeholder="e.g. Bond paper for printing, Grab to client site" maxlength="255">
                        @error('particular')
                            <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cost_code">Cost Code</label>
                        <input type="text" name="cost_code" id="cost_code" class="form-control" value="{{ old('cost_code') }}" placeholder="e.g. CC-001" maxlength="100" required>
                        @error('cost_code')
                            <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (₱)</label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" max="{{ $fund->current_balance }}" value="{{ old('amount') }}" required placeholder="0.00">
                        @error('amount')
                            <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="expense_date">Date</label>
                        <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="receipt_number">Receipt # <span style="color:var(--text-muted);text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="receipt_number" id="receipt_number" class="form-control" value="{{ old('receipt_number') }}" placeholder="e.g. REC-001">
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Record Expense
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- FUND INFO SIDEBAR --}}
    <div>
        <div class="card" style="border-top:3px solid var(--primary);">
            <div class="card-body">
                <div style="text-align:center;margin-bottom:16px;">
                    <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);font-weight:600;">Fund #{{ $fund->id }}</div>
                    <div style="font-size:2rem;font-weight:800;margin-top:4px;font-family:'SF Mono','Cascadia Code',monospace;">₱{{ number_format($fund->current_balance, 2) }}</div>
                    <div style="font-size:0.8rem;color:var(--text-secondary);">available balance</div>
                </div>

                <div style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:4px;">
                        <span style="color:var(--text-secondary);font-weight:500;">Expense Utilization</span>
                        <span style="font-weight:600;">{{ number_format($expensePct, 1) }}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill {{ $fillClass }}" style="width:{{ min($expensePct, 100) }}%"></div>
                    </div>
                </div>

                <div style="border-top:1px solid var(--border);padding-top:14px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:0.8rem;color:var(--text-secondary);">Allocated</span>
                        <span style="font-size:0.8rem;font-weight:600;" class="text-mono">₱{{ number_format($fund->total_amount, 2) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:0.8rem;color:var(--text-secondary);">Spent</span>
                        <span style="font-size:0.8rem;font-weight:600;" class="text-mono">₱{{ number_format($totalExpenses, 2) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-size:0.8rem;color:var(--text-secondary);">30% Expense Threshold</span>
                        <span style="font-size:0.8rem;font-weight:600;" class="text-mono">₱{{ number_format($fund->total_amount * 0.30, 2) }}</span>
                    </div>
                    @if($totalExpenses >= $fund->total_amount * 0.30)
                    <div style="margin-top:10px;padding:8px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;font-size:0.78rem;color:var(--danger);font-weight:600;text-align:center;">
                        Liquidate & Replenish
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
