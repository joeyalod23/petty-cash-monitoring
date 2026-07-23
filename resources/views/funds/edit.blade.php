@extends('layouts.app')

@section('title', 'Edit Fund #' . $fund->id)
@section('page-title', 'Edit Fund')

@section('content')
<a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
</a>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <h3>Edit Fund #{{ $fund->id }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('funds.update', $fund) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="total_amount">Allocation Amount (₱)</label>
                    <input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" min="1" value="{{ old('total_amount', $fund->total_amount) }}" required>
                    @error('total_amount')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
                <div style="display:flex;gap:10px;margin-top:8px;">
                    <button type="submit" class="btn btn-primary">Update Fund</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div>
        <div class="card" style="border-top:3px solid var(--primary);">
            <div class="card-body">
                <div style="text-align:center;">
                    <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);font-weight:600;">Fund #{{ $fund->id }}</div>
                    <div style="font-size:1.6rem;font-weight:800;margin-top:4px;font-family:'SF Mono','Cascadia Code',monospace;">₱{{ number_format($fund->current_balance, 2) }}</div>
                    <div style="font-size:0.8rem;color:var(--text-secondary);">current balance</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
