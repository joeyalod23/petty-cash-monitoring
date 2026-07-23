<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\PettyCashFund;
use App\Models\ReplenishmentRequest;
use App\Services\PettyCashService;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    public function __construct(
        private PettyCashService $service,
    ) {}

    public function dashboard()
    {
        $funds = PettyCashFund::withCount('expenses')->get();
        $recentExpenses = Expense::with('fund')->latest()->take(10)->get();
        $pendingReplenishments = ReplenishmentRequest::where('status', 'pending')->with('fund')->get();

        return view('dashboard', compact('funds', 'recentExpenses', 'pendingReplenishments'));
    }

    public function storeFund(Request $request)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:1',
        ]);

        $this->service->createFund($validated['total_amount']);

        return redirect()->route('dashboard')->with('success', 'Petty cash fund created successfully.');
    }

    public function createExpense(PettyCashFund $fund)
    {
        return view('expenses.create', ['fund' => $fund]);
    }

    public function storeExpense(Request $request, PettyCashFund $fund)
    {
        $validated = $request->validate([
            'payee' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'receipt_number' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
        ]);

        try {
            $result = $this->service->recordExpense($fund, $validated);

            $message = 'Expense logged successfully.';
            if ($result['alert_triggered']) {
                $message .= ' Balance is below 30% threshold - replenishment request auto-generated.';
            }

            return redirect()->route('fund.expenses', $fund)
                ->with('success', $message);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function fundExpenses(PettyCashFund $fund)
    {
        $expenses = $fund->expenses()->latest('expense_date')->paginate(15);

        return view('expenses.index', compact('fund', 'expenses'));
    }

    public function replenishments()
    {
        $requests = ReplenishmentRequest::with('fund')->latest()->paginate(15);

        return view('replenishments.index', compact('requests'));
    }

    public function approveReplenishment(ReplenishmentRequest $request)
    {
        $this->service->approveReplenishment($request);

        return redirect()->route('replenishments')
            ->with('success', 'Replenishment request approved.');
    }

    public function disburseReplenishment(ReplenishmentRequest $request)
    {
        $this->service->disburseReplenishment($request);

        return redirect()->route('replenishments')
            ->with('success', 'Replenishment disbursed. Fund balance restored to full amount.');
    }

    public function rejectReplenishment(ReplenishmentRequest $request)
    {
        $this->service->rejectReplenishment($request);

        return redirect()->route('replenishments')
            ->with('success', 'Replenishment request rejected.');
    }
}
