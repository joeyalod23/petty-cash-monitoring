@extends('layouts.app')
@php
    $totalExpenses = $fund->total_amount - $fund->current_balance;
    $expensePct = $fund->total_amount > 0 ? ($totalExpenses / $fund->total_amount) * 100 : 0;
    $fillClass = $expensePct > 50 ? 'red' : ($expensePct > 30 ? 'yellow' : 'green');
@endphp

@section('title', 'Expenses - Fund #' . $fund->id)
@section('page-title', 'Expense History')

@section('content')
<a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
</a>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Balance</div>
            <div class="value">₱{{ number_format($fund->current_balance, 2) }}</div>
            <div class="sub">of ₱{{ number_format($fund->total_amount, 2) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Total Spent</div>
            <div class="value">₱{{ number_format($fund->total_amount - $fund->current_balance, 2) }}</div>
            <div class="sub">{{ $expenses->total() }} transactions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">30% Expense Threshold</div>
            <div class="value">₱{{ number_format($fund->total_amount * 0.30, 2) }}</div>
            <div class="sub" style="color:{{ $totalExpenses >= ($fund->total_amount * 0.30) ? 'var(--danger)' : 'var(--text-muted)' }}; font-weight:{{ $totalExpenses >= ($fund->total_amount * 0.30) ? '600' : '400' }};">
                {{ $totalExpenses >= ($fund->total_amount * 0.30) ? 'LIQUIDATE & REPLENISH' : 'Above threshold' }}
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Expenses - Fund #{{ $fund->id }}</h3>
        <a href="{{ route('fund.expenses.create', $fund) }}" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Expense
        </a>
    </div>
    @if($expenses->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No expenses recorded for this fund yet.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payee</th>
                    <th>Category</th>
                    <th>Receipt #</th>
                    <th style="text-align:right;">Amount</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $exp)
                <tr>
                    <td style="color:var(--text-secondary);">{{ $exp->expense_date->format('M d, Y') }}</td>
                    <td style="font-weight:500;">{{ $exp->payee }}</td>
                    <td><span style="font-size:0.78rem;background:var(--bg);padding:3px 10px;border-radius:6px;font-weight:500;color:var(--text-secondary);">{{ $exp->category }}</span></td>
                    <td style="color:var(--text-muted);font-size:0.82rem;">{{ $exp->receipt_number ?? '-' }}</td>
                    <td style="text-align:right;font-weight:700;font-family:'SF Mono','Cascadia Code',monospace;color:var(--danger);">-₱{{ number_format($exp->amount, 2) }}</td>
                    <td>
                        @if(Auth::user()->isAdmin())
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="{{ route('expenses.edit', $exp) }}" class="btn btn-ghost btn-sm">Edit</a>
                            <form action="{{ route('expenses.destroy', $exp) }}" method="POST" onsubmit="return confirm('Delete this expense? The amount will be returned to the fund balance.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Delete</button>
                            </form>
                        </div>
                        @else -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:0 24px;">
            {{ $expenses->links() }}
        </div>
    @endif
</div>
@endsection
