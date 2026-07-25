<?php

namespace App\Http\Controllers;

use App\Models\ReplenishmentReport;
use App\Models\ReplenishmentItem;
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
        return view('reports.create');
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

            foreach ($validated['items'] as $item) {
                $report->items()->create([
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
