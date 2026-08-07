<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * List expenses with filters (category, date range, search note).
     */
    public function index(Request $request)
    {
        $query = Expense::query()->with(['category', 'createdBy']);

        if ($search = $request->input('search')) {
            $query->where('note', 'like', "%{$search}%");
        }

        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', (int) $request->input('expense_category_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->input('to_date'));
        }

        $expenses = $query->latest('expense_date')->latest('id')
            ->paginate(15)->withQueryString();

        return view('expenses.index', [
            'expenses'   => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'total'      => (clone $query)->sum('amount'),
        ]);
    }

    public function create()
    {
        return view('expenses.create', [
            'categories' => ExpenseCategory::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateExpense($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        Expense::create($data);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense added successfully!');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', [
            'expense'    => $expense,
            'categories' => ExpenseCategory::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $this->validateExpense($request);
        $data['updated_by'] = auth()->id();

        $expense->update($data);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully!');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully!');
    }

    /**
     * Profit & Loss = collections (payments) − expenses, for a period.
     */
    public function profitLoss(Request $request)
    {
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to = $request->input('to_date', now()->endOfMonth()->toDateString());

        $collections = (float) Payment::query()
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');

        $expenseByCategory = Expense::query()
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        $totalExpense = (float) $expenseByCategory->sum('total');

        return view('expenses.profit_loss', [
            'from'              => $from,
            'to'                => $to,
            'collections'       => $collections,
            'expenseByCategory' => $expenseByCategory,
            'totalExpense'      => $totalExpense,
            'net'               => round($collections - $totalExpense, 2),
        ]);
    }

    protected function validateExpense(Request $request): array
    {
        return $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'expense_date'        => 'required|date',
            'note'                => 'nullable|string|max:255',
        ]);
    }
}
