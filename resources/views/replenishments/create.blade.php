@extends('layouts.app')

@section('title', 'New Replenishment Request')
@section('page-title', 'New Replenishment Request')

@section('content')
<a href="{{ route('replenishments') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Replenishments
</a>

<div class="card" style="max-width:600px;">
    <div class="card-header">
        <h3>Create Replenishment Request</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('replenishments.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="fund_id">Fund</label>
                <select name="fund_id" id="fund_id" class="form-control" required>
                    <option value="">Select a fund</option>
                    @foreach($funds as $fund)
                        <option value="{{ $fund->id }}" {{ old('fund_id') == $fund->id ? 'selected' : '' }}>
                            Fund #{{ $fund->id }} — ₱{{ number_format($fund->current_balance, 2) }} balance / ₱{{ number_format($fund->total_amount, 2) }} total
                        </option>
                    @endforeach
                </select>
                @error('fund_id')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="requested_amount">Requested Amount (₱)</label>
                <input type="number" name="requested_amount" id="requested_amount" class="form-control" step="0.01" min="1" value="{{ old('requested_amount') }}" required placeholder="e.g. 5000.00">
                @error('requested_amount')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="{{ route('replenishments') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
