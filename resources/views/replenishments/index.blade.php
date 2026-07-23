@extends('layouts.app')
@php
    $pendingCount = $requests->getCollection()->filter(fn($r) => $r->status === 'pending')->count();
    $approvedCount = $requests->getCollection()->filter(fn($r) => $r->status === 'approved')->count();
    $disbursedCount = $requests->getCollection()->filter(fn($r) => $r->status === 'disbursed')->count();
    $totalRequested = $requests->getCollection()->sum('requested_amount');
@endphp

@section('title', 'Replenishment Requests')
@section('page-title', 'Replenishment Requests')

@section('content')
<a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
</a>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Pending</div>
            <div class="value">{{ $pendingCount }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14L23 10"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Approved</div>
            <div class="value">{{ $approvedCount }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Disbursed</div>
            <div class="value">{{ $disbursedCount }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <div class="label">Total Requested</div>
            <div class="value">₱{{ number_format($totalRequested, 2) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Requests</h3>
    </div>
    @if($requests->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            <p>No replenishment requests found.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fund</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Triggered By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td><span class="text-mono" style="font-weight:600;">#{{ $req->id }}</span></td>
                    <td>
                        <a href="{{ route('fund.expenses', $req->fund_id) }}" style="color:var(--primary);text-decoration:none;font-weight:500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            Fund #{{ $req->fund_id }}
                        </a>
                    </td>
                    <td class="text-mono" style="font-weight:700;">₱{{ number_format($req->requested_amount, 2) }}</td>
                    <td><span class="badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                    <td style="color:var(--text-secondary);font-size:0.82rem;">{{ $req->triggered_by }}</td>
                    <td style="color:var(--text-secondary);">{{ $req->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        @if($req->status === 'pending')
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
                        @elseif($req->status === 'approved')
                            <form action="{{ route('replenishments.disburse', $req) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                    Disburse
                                </button>
                            </form>
                        @else
                            <span style="color:var(--text-muted);font-size:0.82rem;">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:0 24px;">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
