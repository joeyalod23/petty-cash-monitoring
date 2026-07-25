<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replenishment Report #{{ $report->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1b2559;
            --primary-light: #2d3a7a;
            --border: #d1d5db;
            --border-dark: #1b2559;
            --text: #1b2559;
            --text-secondary: #68769f;
            --danger: #ee5d50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 11px;
            color: var(--text);
            background: #f4f6f9;
            line-height: 1.4;
        }

        .no-print {
            padding: 16px 24px;
            display: flex;
            gap: 10px;
            align-items: center;
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .no-print a, .no-print button {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }

        .no-print .btn-back {
            color: var(--text-secondary);
            background: transparent;
        }

        .no-print .btn-back:hover {
            color: var(--primary);
        }

        .no-print .btn-print {
            background: var(--primary);
            color: #fff;
            margin-left: auto;
        }

        .no-print .btn-print:hover {
            background: var(--primary-light);
        }

        .report-container {
            max-width: 900px;
            margin: 24px auto;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .company-header {
            background: var(--primary);
            color: #fff;
            padding: 24px 40px;
            text-align: center;
        }

        .company-header h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 2px solid var(--border);
        }

        .info-left, .info-right {
            padding: 16px 40px;
        }

        .info-right {
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            color: var(--text-secondary);
            min-width: 80px;
            flex-shrink: 0;
        }

        .info-value {
            font-weight: 600;
            font-size: 11px;
        }

        .section-bar {
            background: var(--primary);
            color: #fff;
            padding: 10px 40px;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .cash-received-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 40px;
            background: #f8fafc;
            border-bottom: 2px solid var(--border);
            font-weight: 700;
            font-size: 11px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead th {
            background: #f1f5f9;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border);
            text-align: left;
            white-space: nowrap;
        }

        .items-table thead th:last-child {
            text-align: right;
        }

        .items-table tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-size: 10.5px;
            vertical-align: middle;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid var(--border);
        }

        .items-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
            font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
            white-space: nowrap;
        }

        .items-table tbody td.cost-code-cell,
        .items-table tbody td.particulars-cell {
            font-weight: 600;
        }

        .items-table tfoot td {
            padding: 10px 12px;
            font-weight: 700;
            font-size: 11px;
        }

        .items-table tfoot tr.total-row td {
            border-top: 2px solid var(--border-dark);
            border-bottom: 1px solid var(--border-dark);
        }

        .items-table tfoot tr.return-row td {
            border-bottom: 3px double var(--border-dark);
            padding-bottom: 14px;
        }

        .items-table tfoot td:last-child {
            text-align: right;
            font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        }

        .signature-block {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            padding: 40px;
            margin-top: 32px;
        }

        .signature-col {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid var(--text);
            margin-top: 60px;
            padding-top: 8px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .print-footer {
            text-align: center;
            padding: 16px 40px;
            font-size: 9px;
            color: var(--text-secondary);
            border-top: 1px solid var(--border);
        }

        @media print {
            body {
                background: #fff;
                font-size: 10px;
            }

            .no-print {
                display: none !important;
            }

            .report-container {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .company-header {
                padding: 20px 32px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .company-header h1 {
                font-size: 18px;
            }

            .info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .info-left, .info-right {
                padding: 12px 32px;
            }

            .section-bar {
                padding: 8px 32px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .cash-received-row {
                padding: 10px 32px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .items-table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .items-table tbody td {
                padding: 6px 10px;
                font-size: 9.5px;
            }

            .items-table thead th {
                font-size: 8px;
                padding: 8px 10px;
            }

            .signature-block {
                padding: 32px;
                page-break-inside: avoid;
            }

            .signature-line {
                margin-top: 50px;
            }

            @page {
                size: A4 portrait;
                margin: 15mm 12mm 15mm 12mm;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="{{ route('reports.index') }}" class="btn-back">
        <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Reports
    </a>
    @if(Auth::user()->isAdmin())
    <a href="{{ route('reports.edit', $report) }}" class="btn-back" style="text-decoration:none;">
        <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit
    </a>
    @endif
    <a href="{{ route('reports.export', $report) }}" class="btn-back" style="text-decoration:none;background:#10b981;color:#fff;padding:8px 16px;border-radius:8px;font-weight:500;font-size:0.82rem;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
        <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download Excel
    </a>
    <button class="btn-print" onclick="window.print()">
        <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print
    </button>
</div>

<div class="report-container">
    <div class="company-header">
        <h1>L.V. LEDESMA CONSTRUCTION, INC.</h1>
    </div>

    <div class="info-grid">
        <div class="info-left">
            <div class="info-row">
                <span class="info-label">Project:</span>
                <span class="info-value">{{ $report->project_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span class="info-value">{{ $report->location }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Subject:</span>
                <span class="info-value">{{ $report->subject }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Period:</span>
                <span class="info-value">{{ $report->period_start->format('M d, Y') }} - {{ $report->period_end->format('M d, Y') }}</span>
            </div>
        </div>
        <div class="info-right">
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $report->report_date->format('F d, Y') }}</span>
            </div>
        </div>
    </div>

    <div class="section-bar">Liquidation</div>

    <div class="cash-received-row">
        <span>Cash Received</span>
        <span style="font-family:'SF Mono','Cascadia Code','Consolas',monospace;">₱{{ number_format($report->cash_received, 2) }}</span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher No.</th>
                <th>Reference Number</th>
                <th>Payee</th>
                <th>Cost Code</th>
                <th>Particulars</th>
                <th style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedItems as $group)
            <tr>
                <td>{{ $group['item']->expense_date->format('m/d/Y') }}</td>
                <td>{{ $group['item']->voucher_no }}</td>
                <td>{{ $group['item']->reference_no ?? '-' }}</td>
                <td>{{ $group['item']->payee }}</td>
                @if($group['is_first'])
                    <td class="cost-code-cell" rowspan="{{ $group['rowspan'] }}">{{ $group['item']->cost_code }}</td>
                    <td class="particulars-cell" rowspan="{{ $group['rowspan'] }}">{{ $group['item']->particulars }}</td>
                @endif
                <td style="text-align:right;font-family:'SF Mono','Cascadia Code',Consolas,monospace;">₱{{ number_format($group['item']->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;color:var(--text-secondary);padding:24px;">No items recorded.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align:right;">Total Liquidated Amount</td>
                <td>₱{{ number_format($report->total_liquidated, 2) }}</td>
            </tr>
            <tr class="return-row">
                <td colspan="6" style="text-align:right;">For Return / (Reimbursement)</td>
                <td style="color:{{ $report->for_return < 0 ? 'var(--danger)' : 'inherit' }}">₱{{ number_format($report->for_return, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-block">
        <div class="signature-col">
            <div class="signature-line">Prepared By</div>
            <div style="margin-top:8px;font-weight:600;font-size:10px;">{{ $report->prepared_by }}</div>
        </div>
        <div class="signature-col">
            <div class="signature-line">Noted and Reviewed By</div>
            <div style="margin-top:8px;font-weight:600;font-size:10px;">{{ $report->reviewed_by }}</div>
        </div>
        <div class="signature-col">
            <div class="signature-line">Checked & Verified By</div>
            <div style="margin-top:8px;font-weight:600;font-size:10px;">{{ $report->verified_by }}</div>
        </div>
    </div>

    <div class="print-footer">
        Generated on {{ now()->format('F d, Y \a\t h:i A') }} &mdash; Petty Cash Monitoring System
    </div>
</div>

</body>
</html>
