@extends('layouts.app')

@section('title', 'Edit Replenishment #' . $request->id)
@section('page-title', 'Edit Replenishment')

@section('content')
<a href="{{ route('replenishments') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Replenishments
</a>

<div class="card" style="max-width:600px;">
    <div class="card-header">
        <h3>Edit Replenishment #{{ $request->id }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('replenishments.update', $request) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="fund_id">Fund</label>
                <select name="fund_id" id="fund_id" class="form-control" required>
                    <option value="">Select a fund</option>
                    @foreach($funds as $fund)
                        <option value="{{ $fund->id }}" {{ old('fund_id', $request->fund_id) == $fund->id ? 'selected' : '' }}>
                            Fund #{{ $fund->id }} — ₱{{ number_format($fund->current_balance, 2) }} balance
                        </option>
                    @endforeach
                </select>
                @error('fund_id')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="requested_amount">Requested Amount (₱)</label>
                <input type="number" name="requested_amount" id="requested_amount" class="form-control" step="0.01" min="1" value="{{ old('requested_amount', $request->requested_amount) }}" required>
                @error('requested_amount')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control" required>
                    @foreach(['pending','approved','rejected','disbursed'] as $s)
                        <option value="{{ $s }}" {{ old('status', $request->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">Update Request</button>
                <a href="{{ route('replenishments') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
