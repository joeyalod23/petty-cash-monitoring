@extends('layouts.app')

@section('title', 'Create Replenishment Report')
@section('page-title', 'Create Replenishment Report')

@section('content')
<a href="{{ route('reports.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-secondary);text-decoration:none;font-size:0.82rem;font-weight:500;margin-bottom:16px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-secondary)'">
    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Reports
</a>

<form action="{{ route('reports.store') }}" method="POST" id="reportForm">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3>Report Information</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="project_name">Project Name</label>
                    <input type="text" name="project_name" id="project_name" class="form-control" value="{{ old('project_name') }}" required placeholder="e.g. Office Renovation">
                    @error('project_name')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" required placeholder="e.g. Manila, Philippines">
                    @error('location')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject') }}" required placeholder="e.g. Petty Cash Replenishment">
                @error('subject')
                    <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="period_start">Period Start</label>
                    <input type="date" name="period_start" id="period_start" class="form-control" value="{{ old('period_start') }}" required>
                    @error('period_start')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="period_end">Period End</label>
                    <input type="date" name="period_end" id="period_end" class="form-control" value="{{ old('period_end') }}" required>
                    @error('period_end')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="report_date">Report Date</label>
                    <input type="date" name="report_date" id="report_date" class="form-control" value="{{ old('report_date', date('Y-m-d')) }}" required>
                    @error('report_date')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cash_received">Cash Received (₱)</label>
                    <input type="number" name="cash_received" id="cash_received" class="form-control" step="0.01" min="0" value="{{ old('cash_received') }}" required placeholder="0.00">
                    @error('cash_received')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="prepared_by">Prepared By</label>
                    <input type="text" name="prepared_by" id="prepared_by" class="form-control" value="{{ old('prepared_by') }}" required placeholder="Name">
                    @error('prepared_by')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="reviewed_by">Reviewed By</label>
                    <input type="text" name="reviewed_by" id="reviewed_by" class="form-control" value="{{ old('reviewed_by') }}" required placeholder="Name">
                    @error('reviewed_by')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="verified_by">Verified By</label>
                    <input type="text" name="verified_by" id="verified_by" class="form-control" value="{{ old('verified_by') }}" required placeholder="Name">
                    @error('verified_by')
                        <div style="color:var(--danger);font-size:0.8rem;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3>Expense Items</h3>
            <button type="button" onclick="addItem()" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Item
            </button>
        </div>
        <div class="card-body" style="padding:0;">
            <div style="overflow-x:auto;">
                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width:11%;">Date</th>
                            <th style="width:10%;">Voucher No.</th>
                            <th style="width:10%;">Reference No.</th>
                            <th style="width:14%;">Payee</th>
                            <th style="width:10%;">Cost Code</th>
                            <th style="width:18%;">Particulars</th>
                            <th style="width:12%;">Amount</th>
                            <th style="width:5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align:right;font-weight:700;border-top:2px solid var(--border);">Total Liquidated Amount</td>
                            <td style="text-align:right;font-weight:700;border-top:2px solid var(--border);" class="text-mono" id="totalLiquidated">₱0.00</td>
                            <td style="border-top:2px solid var(--border);"></td>
                        </tr>
                        <tr>
                            <td colspan="6" style="text-align:right;font-weight:700;">For Return / (Reimbursement)</td>
                            <td style="text-align:right;font-weight:700;" class="text-mono" id="forReturn">₱0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @error('items')
                <div style="color:var(--danger);font-size:0.8rem;padding:12px 24px;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-bottom:48px;">
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Create Report
        </button>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@endsection

@section('scripts')
<script>
    let itemIndex = 0;

    function addItem() {
        const tbody = document.getElementById('itemsBody');
        const row = document.createElement('tr');
        row.id = 'item-' + itemIndex;
        row.innerHTML = `
            <td><input type="date" name="items[${itemIndex}][expense_date]" class="form-control" style="font-size:0.78rem;padding:6px 8px;" required></td>
            <td><input type="text" name="items[${itemIndex}][voucher_no]" class="form-control" style="font-size:0.78rem;padding:6px 8px;" required placeholder="Voucher #"></td>
            <td><input type="text" name="items[${itemIndex}][reference_no]" class="form-control" style="font-size:0.78rem;padding:6px 8px;" placeholder="Optional"></td>
            <td><input type="text" name="items[${itemIndex}][payee]" class="form-control" style="font-size:0.78rem;padding:6px 8px;" required placeholder="Payee name"></td>
            <td><input type="text" name="items[${itemIndex}][cost_code]" class="form-control" style="font-size:0.78rem;padding:6px 8px;" required placeholder="Code"></td>
            <td><input type="text" name="items[${itemIndex}][particulars]" class="form-control" style="font-size:0.78rem;padding:6px 8px;" required placeholder="Description"></td>
            <td><input type="number" name="items[${itemIndex}][amount]" class="form-control item-amount" style="font-size:0.78rem;padding:6px 8px;" step="0.01" min="0.01" required placeholder="0.00" oninput="recalculate()"></td>
            <td><button type="button" onclick="removeItem(${itemIndex})" class="btn btn-ghost btn-sm" style="color:var(--danger);padding:4px;">&times;</button></td>
        `;
        tbody.appendChild(row);
        itemIndex++;
    }

    function removeItem(index) {
        const row = document.getElementById('item-' + index);
        if (row) {
            row.remove();
            recalculate();
        }
    }

    function recalculate() {
        let total = 0;
        document.querySelectorAll('.item-amount').forEach(function(input) {
            const val = parseFloat(input.value);
            if (!isNaN(val)) {
                total += val;
            }
        });
        const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
        const forReturn = cashReceived - total;

        document.getElementById('totalLiquidated').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('forReturn').textContent = '₱' + forReturn.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('forReturn').style.color = forReturn < 0 ? 'var(--danger)' : '';
    }

    document.getElementById('cash_received').addEventListener('input', recalculate);

    addItem();
</script>
@endsection
