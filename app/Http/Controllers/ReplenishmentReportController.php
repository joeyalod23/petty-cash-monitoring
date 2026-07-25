<?php

namespace App\Http\Controllers;

use App\Models\ReplenishmentReport;
use App\Models\ReplenishmentItem;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReplenishmentReportController extends Controller
{
    public function index()
    {
        $reports = ReplenishmentReport::latest('report_date')->paginate(15);

        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        $expenses = Expense::with('fund')
            ->where('status', 'open')
            ->latest('expense_date')
            ->get();

        $totalLiquidation = (float) $expenses->sum('amount');

        return view('reports.create', compact('expenses', 'totalLiquidation'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'report_date' => 'required|date',
            'cash_received' => 'required|numeric|min:0',
            'prepared_by' => 'required|string|max:255',
            'reviewed_by' => 'required|string|max:255',
            'verified_by' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.expense_id' => 'nullable|exists:expenses,id',
            'items.*.expense_date' => 'required|date',
            'items.*.voucher_no' => 'required|string|max:100',
            'items.*.reference_no' => 'nullable|string|max:100',
            'items.*.payee' => 'required|string|max:255',
            'items.*.cost_code' => 'required|string|max:100',
            'items.*.particulars' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        $report = DB::transaction(function () use ($validated) {
            $report = ReplenishmentReport::create([
                'project_name' => $validated['project_name'],
                'location' => $validated['location'],
                'subject' => $validated['subject'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'report_date' => $validated['report_date'],
                'cash_received' => $validated['cash_received'],
                'prepared_by' => $validated['prepared_by'],
                'reviewed_by' => $validated['reviewed_by'],
                'verified_by' => $validated['verified_by'],
            ]);

            $expenseIds = [];
            foreach ($validated['items'] as $item) {
                $report->items()->create([
                    'expense_id' => $item['expense_id'] ?? null,
                    'expense_date' => $item['expense_date'],
                    'voucher_no' => $item['voucher_no'],
                    'reference_no' => $item['reference_no'] ?? null,
                    'payee' => $item['payee'],
                    'cost_code' => $item['cost_code'],
                    'particulars' => $item['particulars'],
                    'amount' => $item['amount'],
                    'group_key' => $item['cost_code'] . '|' . $item['particulars'],
                ]);
                if (!empty($item['expense_id'])) {
                    $expenseIds[] = $item['expense_id'];
                }
            }

            if (!empty($expenseIds)) {
                Expense::whereIn('id', $expenseIds)->update(['status' => 'closed']);
            }

            return $report;
        });

        return redirect()->route('reports.show', $report)
            ->with('success', 'Replenishment report created successfully.');
    }

    public function show(ReplenishmentReport $report)
    {
        $report->load('items');
        $groupedItems = $this->buildGroupedItems($report->items);

        return view('reports.show', compact('report', 'groupedItems'));
    }

    public function edit(ReplenishmentReport $report)
    {
        $report->load('items');

        return view('reports.edit', compact('report'));
    }

    public function update(Request $request, ReplenishmentReport $report)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'report_date' => 'required|date',
            'cash_received' => 'required|numeric|min:0',
            'prepared_by' => 'required|string|max:255',
            'reviewed_by' => 'required|string|max:255',
            'verified_by' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.expense_id' => 'nullable|exists:expenses,id',
            'items.*.expense_date' => 'required|date',
            'items.*.voucher_no' => 'required|string|max:100',
            'items.*.reference_no' => 'nullable|string|max:100',
            'items.*.payee' => 'required|string|max:255',
            'items.*.cost_code' => 'required|string|max:100',
            'items.*.particulars' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $report) {
            $report->update([
                'project_name' => $validated['project_name'],
                'location' => $validated['location'],
                'subject' => $validated['subject'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'report_date' => $validated['report_date'],
                'cash_received' => $validated['cash_received'],
                'prepared_by' => $validated['prepared_by'],
                'reviewed_by' => $validated['reviewed_by'],
                'verified_by' => $validated['verified_by'],
            ]);

            $report->items()->delete();

            foreach ($validated['items'] as $item) {
                $report->items()->create([
                    'expense_id' => $item['expense_id'] ?? null,
                    'expense_date' => $item['expense_date'],
                    'voucher_no' => $item['voucher_no'],
                    'reference_no' => $item['reference_no'] ?? null,
                    'payee' => $item['payee'],
                    'cost_code' => $item['cost_code'],
                    'particulars' => $item['particulars'],
                    'amount' => $item['amount'],
                    'group_key' => $item['cost_code'] . '|' . $item['particulars'],
                ]);
            }
        });

        return redirect()->route('reports.show', $report)
            ->with('success', 'Replenishment report updated successfully.');
    }

    public function destroy(ReplenishmentReport $report)
    {
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Replenishment report deleted successfully.');
    }

    public function export(ReplenishmentReport $report)
    {
        $report->load('items');
        $items = $report->items()->orderBy('cost_code')->orderBy('particulars')->orderBy('expense_date')->get();

        $filename = 'Replenishment_Report_' . $report->id . '_' . $report->report_date->format('Y-m-d') . '.xls';

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Report</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        $html .= '<body>';
        $html .= '<table border="1">';

        $html .= '<tr><td colspan="7" style="background:#1b2559;color:#fff;font-size:14pt;font-weight:bold;text-align:center;padding:10px;">L.V. LEDESMA CONSTRUCTION, INC.</td></tr>';
        $html .= '<tr><td colspan="7" style="background:#1b2559;color:#fff;font-size:11pt;font-weight:bold;text-align:center;padding:5px;">REPLENISHMENT REPORT</td></tr>';
        $html .= '<tr><td colspan="7"></td></tr>';

        $html .= '<tr><td style="font-weight:bold;width:120px;">Project:</td><td>' . htmlspecialchars($report->project_name) . '</td><td></td><td style="font-weight:bold;width:80px;">Date:</td><td colspan="3">' . $report->report_date->format('F d, Y') . '</td></tr>';
        $html .= '<tr><td style="font-weight:bold;">Location:</td><td>' . htmlspecialchars($report->location) . '</td><td></td><td></td><td colspan="3"></td></tr>';
        $html .= '<tr><td style="font-weight:bold;">Subject:</td><td>' . htmlspecialchars($report->subject) . '</td><td></td><td></td><td colspan="3"></td></tr>';
        $html .= '<tr><td style="font-weight:bold;">Period:</td><td colspan="3">' . $report->period_start->format('M d, Y') . ' - ' . $report->period_end->format('M d, Y') . '</td><td colspan="3"></td></tr>';
        $html .= '<tr><td colspan="7"></td></tr>';

        $html .= '<tr><td colspan="7" style="background:#1b2559;color:#fff;font-weight:bold;padding:5px;">LIQUIDATION</td></tr>';
        $html .= '<tr><td colspan="5" style="font-weight:bold;">Cash Received:</td><td colspan="2" style="font-weight:bold;text-align:right;">₱' . number_format($report->cash_received, 2) . '</td></tr>';
        $html .= '<tr><td colspan="7"></td></tr>';

        $html .= '<tr style="background:#e2e8f0;font-weight:bold;">';
        $html .= '<td style="font-size:10pt;">Date</td>';
        $html .= '<td style="font-size:10pt;">Voucher No.</td>';
        $html .= '<td style="font-size:10pt;">Reference No.</td>';
        $html .= '<td style="font-size:10pt;">Payee</td>';
        $html .= '<td style="font-size:10pt;">Cost Code</td>';
        $html .= '<td style="font-size:10pt;">Particulars</td>';
        $html .= '<td style="font-size:10pt;text-align:right;">Amount</td>';
        $html .= '</tr>';

        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += (float) $item->amount;
            $html .= '<tr>';
            $html .= '<td>' . $item->expense_date->format('m/d/Y') . '</td>';
            $html .= '<td>' . htmlspecialchars($item->voucher_no) . '</td>';
            $html .= '<td>' . htmlspecialchars($item->reference_no ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($item->payee) . '</td>';
            $html .= '<td>' . htmlspecialchars($item->cost_code) . '</td>';
            $html .= '<td>' . htmlspecialchars($item->particulars) . '</td>';
            $html .= '<td style="text-align:right;">₱' . number_format($item->amount, 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr><td colspan="6" style="font-weight:bold;text-align:right;border-top:2px solid #000;">Total Liquidated Amount</td><td style="font-weight:bold;text-align:right;border-top:2px solid #000;">₱' . number_format($totalAmount, 2) . '</td></tr>';
        $forReturn = (float) $report->cash_received - $totalAmount;
        $html .= '<tr><td colspan="6" style="font-weight:bold;text-align:right;">For Return / (Reimbursement)</td><td style="font-weight:bold;text-align:right;color:' . ($forReturn < 0 ? 'red' : 'black') . ';">₱' . number_format($forReturn, 2) . '</td></tr>';

        $html .= '<tr><td colspan="7"></td></tr>';
        $html .= '<tr><td colspan="2" style="text-align:center;font-weight:bold;">Prepared By</td><td></td><td colspan="2" style="text-align:center;font-weight:bold;">Reviewed By</td><td colspan="2" style="text-align:center;font-weight:bold;">Verified By</td></tr>';
        $html .= '<tr><td colspan="2" style="text-align:center;padding-top:30px;border-top:1px solid #000;">' . htmlspecialchars($report->prepared_by) . '</td><td></td><td colspan="2" style="text-align:center;padding-top:30px;border-top:1px solid #000;">' . htmlspecialchars($report->reviewed_by) . '</td><td colspan="2" style="text-align:center;padding-top:30px;border-top:1px solid #000;">' . htmlspecialchars($report->verified_by) . '</td></tr>';

        $html .= '</table></body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function buildGroupedItems($items): array
    {
        $sorted = $items->sortBy(['cost_code', 'particulars', 'expense_date'])->values();

        $grouped = [];
        $i = 0;

        while ($i < $sorted->count()) {
            $current = $sorted[$i];
            $rowspan = 1;

            for ($j = $i + 1; $j < $sorted->count(); $j++) {
                if (
                    $sorted[$j]->cost_code === $current->cost_code &&
                    $sorted[$j]->particulars === $current->particulars
                ) {
                    $rowspan++;
                } else {
                    break;
                }
            }

            $grouped[] = [
                'item' => $current,
                'rowspan' => $rowspan,
                'is_first' => true,
            ];

            for ($k = 1; $k < $rowspan; $k++) {
                $grouped[] = [
                    'item' => $sorted[$i + $k],
                    'rowspan' => 0,
                    'is_first' => false,
                ];
            }

            $i += $rowspan;
        }

        return $grouped;
    }
}
