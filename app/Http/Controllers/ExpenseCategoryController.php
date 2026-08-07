<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        return view('expense_categories.index', [
            'categories' => ExpenseCategory::orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('expense_categories.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        ExpenseCategory::create($data);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category added successfully!');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('expense_categories.edit', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $this->validateCategory($request);
        $data['updated_by'] = auth()->id();

        $expenseCategory->update($data);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category updated successfully!');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a category that has expenses.');
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category deleted successfully!');
    }

    protected function validateCategory(Request $request): array
    {
        return $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);
    }
}
