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

    public function editFund(PettyCashFund $fund)
    {
        return view('funds.edit', compact('fund'));
    }

    public function updateFund(Request $request, PettyCashFund $fund)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:1',
        ]);

        $this->service->updateFund($fund, $validated['total_amount']);

        return redirect()->route('dashboard')->with('success', 'Fund updated successfully.');
    }

    public function destroyFund(PettyCashFund $fund)
    {
        $this->service->deleteFund($fund);

        return redirect()->route('dashboard')->with('success', 'Fund deleted successfully.');
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
                $message .= ' Total expenses reached 30% of fund - marked for liquidation & replenishment request auto-generated.';
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

    public function editExpense(Expense $expense)
    {
        $fund = $expense->fund;
        return view('expenses.edit', compact('expense', 'fund'));
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'payee' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'receipt_number' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
        ]);

        try {
            $this->service->updateExpense($expense, $validated);
            return redirect()->route('fund.expenses', $expense->fund)
                ->with('success', 'Expense updated successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function destroyExpense(Expense $expense)
    {
        try {
            $fund = $expense->fund;
            $this->service->deleteExpense($expense);
            return redirect()->route('fund.expenses', $fund)
                ->with('success', 'Expense deleted successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function replenishments()
    {
        $requests = ReplenishmentRequest::with('fund')->latest()->paginate(15);

        return view('replenishments.index', compact('requests'));
    }

    public function createReplenishment()
    {
        $funds = PettyCashFund::all();
        return view('replenishments.create', compact('funds'));
    }

    public function storeReplenishment(Request $request)
    {
        $validated = $request->validate([
            'fund_id' => 'required|exists:petty_cash_funds,id',
            'requested_amount' => 'required|numeric|min:1',
        ]);

        $this->service->createReplenishment($validated['fund_id'], $validated['requested_amount']);

        return redirect()->route('replenishments')
            ->with('success', 'Replenishment request created successfully.');
    }

    public function editReplenishment(ReplenishmentRequest $request)
    {
        $funds = PettyCashFund::all();
        return view('replenishments.edit', ['request' => $request, 'funds' => $funds]);
    }

    public function updateReplenishment(Request $httpRequest, ReplenishmentRequest $request)
    {
        $validated = $httpRequest->validate([
            'fund_id' => 'required|exists:petty_cash_funds,id',
            'requested_amount' => 'required|numeric|min:1',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $this->service->updateReplenishment($request, $validated);

        return redirect()->route('replenishments')
            ->with('success', 'Replenishment request updated successfully.');
    }

    public function destroyReplenishment(ReplenishmentRequest $request)
    {
        $this->service->deleteReplenishment($request);

        return redirect()->route('replenishments')
            ->with('success', 'Replenishment request deleted successfully.');
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
