@extends('layouts.app')
@php
    $fundTarget = 30000;
    $totalAllocated = $fundTarget * max($funds->count(), 1);
    $totalBalance = $funds->sum('current_balance');
    $totalExpenses = $totalAllocated - $totalBalance;
@endphp

@section('page-title', 'Dashboard')

@section('content')
{{-- STATS ROW --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 12h.01"/><path d="M7 12h.01"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Total Funds</div>
            <div class="value">{{ $funds->count() }}</div>
            <div class="sub">Active allocations</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Total Balance</div>
            <div class="value">₱{{ number_format($totalBalance, 2) }}</div>
            <div class="sub">of ₱{{ number_format($totalAllocated, 2) }} allocated</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Total Spent</div>
            <div class="value">₱{{ number_format($totalExpenses, 2) }}</div>
            <div class="sub">{{ $funds->sum(fn($f) => $f->expenses()->count()) }} transactions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Pending Alerts</div>
            <div class="value">{{ $pendingReplenishments->count() }}</div>
            <div class="sub">Replenishment requests</div>
        </div>
    </div>
</div>

{{-- PENDING REPLENISHMENTS --}}
@if($pendingReplenishments->isNotEmpty())
<div class="card" style="border-left: 3px solid var(--warning);">
    <div class="card-header">
        <h3>
            <svg style="width:16px;height:16px;display:inline;vertical-align:-2px;margin-right:6px;color:var(--warning);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Pending Replenishment Requests
        </h3>
        <a href="{{ route('replenishments') }}" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Fund</th>
                <th>Requested Amount</th>
                <th>Triggered By</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingReplenishments as $req)
            <tr>
                <td><span class="text-mono">#{{ $req->fund_id }}</span></td>
                <td><strong>₱{{ number_format($req->requested_amount, 2) }}</strong></td>
                <td style="color:var(--text-secondary);">{{ $req->triggered_by }}</td>
                <td style="color:var(--text-secondary);">{{ $req->created_at->format('M d, Y H:i') }}</td>
                <td>
                    @if(Auth::user()->isAdmin())
                    <div style="display:flex;gap:6px;">
                        <form action="{{ route('replenishments.approve', $req) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form action="{{ route('replenishments.reject', $req) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </div>
                    @else
                    <span style="color:var(--text-muted);font-size:0.82rem;">View only</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- FUNDS TABLE --}}
<div class="card">
    <div class="card-header">
        <h3>Petty Cash Funds</h3>
        @if(Auth::user()->isAdmin())
        <button onclick="document.getElementById('createFundModal').style.display='flex'" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Fund (Replenish)
        </button>
        @endif
    </div>
    @if($funds->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/></svg>
            <p>No funds created yet. Click <strong>"New Fund"</strong> to get started.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fund</th>
                    <th>Allocated</th>
                    <th>Balance</th>
                    <th style="width:22%;">Utilization</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($funds as $fund)
                @php
                    $fundTarget = 30000;
                    $totalFundExpenses = $fundTarget - $fund->current_balance;
                    $expensePct = $fundTarget > 0 ? ($totalFundExpenses / $fundTarget) * 100 : 0;
                    $fillClass = $expensePct > 50 ? 'red' : ($expensePct > 30 ? 'yellow' : 'green');
                    $statusLabel = match($fund->status) {
                        'active' => 'Active',
                        'low_balance' => 'To Liquidate',
                        'replenishment_pending' => 'Pending',
                        default => $fund->status,
                    };
                @endphp
                <tr>
                    <td>
                        <span class="text-mono" style="font-weight:600;">#{{ $fund->id }}</span>
                    </td>
                    <td class="text-mono">₱{{ number_format($fundTarget, 2) }}</td>
                    <td class="text-mono" style="font-weight:600;">₱{{ number_format($fund->current_balance, 2) }}</td>
                    <td>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <span style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);">{{ number_format($expensePct, 1) }}%</span>
                            <span style="font-size:0.72rem;color:var(--text-muted);">30% expense threshold</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill {{ $fillClass }}" style="width:{{ min($expensePct, 100) }}%"></div>
                        </div>
                    </td>
                    <td><span class="badge badge-{{ $fund->status }}">{{ $statusLabel }}</span></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="{{ route('fund.expenses.create', $fund) }}" class="btn btn-primary btn-sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Expense
                            </a>
                            <a href="{{ route('fund.expenses', $fund) }}" class="btn btn-secondary btn-sm">History</a>
                            @if(Auth::user()->isAdmin())
                            <a href="{{ route('funds.edit', $fund) }}" class="btn btn-ghost btn-sm">Edit</a>
                            <form action="{{ route('funds.destroy', $fund) }}" method="POST" onsubmit="return confirm('Delete this fund and all its expenses?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- RECENT EXPENSES --}}
<div class="card">
    <div class="card-header">
        <h3>Recent Transactions</h3>
    </div>
    @if($recentExpenses->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No transactions recorded yet.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Fund</th>
                    <th>Payee</th>
                    <th>Category</th>
                    <th>Particular</th>
                    <th>Cost Code</th>
                    <th>Receipt</th>
                    <th style="text-align:right;">Amount</th>
                    @if(Auth::user()->isAdmin())
                    <th style="text-align:right;">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($recentExpenses as $exp)
                <tr>
                    <td style="color:var(--text-secondary);">{{ $exp->expense_date->format('M d, Y') }}</td>
                    <td><span class="text-mono">#{{ $exp->fund_id }}</span></td>
                    <td style="font-weight:500;">{{ $exp->payee }}</td>
                    <td><span style="font-size:0.78rem;background:var(--bg);padding:3px 10px;border-radius:6px;font-weight:500;color:var(--text-secondary);">{{ $exp->category }}</span></td>
                    <td style="font-size:0.82rem;color:var(--text-secondary);font-style:italic;">{{ $exp->particular ?: '-' }}</td>
                    <td><span style="font-size:0.78rem;background:var(--bg);padding:3px 10px;border-radius:6px;font-weight:600;color:var(--text-secondary);font-family:'SF Mono','Cascadia Code',monospace;">{{ $exp->cost_code }}</span></td>
                    <td style="color:var(--text-muted);font-size:0.82rem;">{{ $exp->receipt_number ?? '-' }}</td>
                    <td style="text-align:right;font-weight:700;font-family:'SF Mono','Cascadia Code',monospace;font-size:0.88rem;">-₱{{ number_format($exp->amount, 2) }}</td>
                    @if(Auth::user()->isAdmin())
                    <td style="text-align:right;">
                        <a href="{{ route('expenses.edit', $exp) }}" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- CREATE FUND MODAL --}}
<div id="createFundModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--surface);border-radius:var(--radius);width:100%;max-width:440px;box-shadow:var(--shadow-lg);overflow:hidden;">
        <div style="padding:24px 28px 0;">
            <h3 style="font-size:1.05rem;font-weight:700;">Add Fund (Replenish to ₱30,000)</h3>
            <p style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;">Amount to add to the existing fund. Fund total is maintained at ₱30,000.</p>
        </div>
        <form action="{{ route('funds.store') }}" method="POST" style="padding:20px 28px 24px;">
            @csrf
            <div class="form-group">
                <label for="total_amount">Amount to Add (₱)</label>
                <input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" min="1" required placeholder="e.g. 10000.00" autofocus>
                @error('total_amount')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="document.getElementById('createFundModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Fund</button>
            </div>
        </form>
    </div>
</div>
@endsection
