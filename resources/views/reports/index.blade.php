@extends('layouts.app')

@section('title', 'Replenishment Reports')
@section('page-title', 'Replenishment Reports')

@section('content')
<a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
</a>

<div class="card">
    <div class="card-header">
        <h3>All Replenishment Reports</h3>
        @if(Auth::user()->isAdmin())
        <a href="{{ route('reports.create') }}" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Report
        </a>
        @endif
    </div>
    @if($reports->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No replenishment reports yet. Click <strong>"New Report"</strong> to create one.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Report #</th>
                    <th>Project</th>
                    <th>Period</th>
                    <th>Report Date</th>
                    <th style="text-align:right;">Cash Received</th>
                    <th style="text-align:right;">Total Liquidated</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                <tr>
                    <td><span class="text-mono" style="font-weight:600;">#{{ $report->id }}</span></td>
                    <td style="font-weight:500;">{{ $report->project_name }}</td>
                    <td style="color:var(--text-secondary);font-size:0.82rem;">
                        {{ $report->period_start->format('M d, Y') }} - {{ $report->period_end->format('M d, Y') }}
                    </td>
                    <td style="color:var(--text-secondary);">{{ $report->report_date->format('M d, Y') }}</td>
                    <td style="text-align:right;font-weight:600;" class="text-mono">₱{{ number_format($report->cash_received, 2) }}</td>
                    <td style="text-align:right;font-weight:600;" class="text-mono">₱{{ number_format($report->total_liquidated, 2) }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="{{ route('reports.show', $report) }}" class="btn btn-primary btn-sm">View</a>
                            @if(Auth::user()->isAdmin())
                            <a href="{{ route('reports.edit', $report) }}" class="btn btn-ghost btn-sm">Edit</a>
                            <form action="{{ route('reports.destroy', $report) }}" method="POST" onsubmit="return confirm('Delete this report and all its items?')">
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
        <div style="padding:0 24px;">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection
