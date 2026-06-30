<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::with(['parent', 'chartOfAccount'])->get();
        $parentCategories = ExpenseCategory::all();
        $expenseAccounts = \App\Models\ChartOfAccount::where('type', 'expense')->get();
        return view('expenses.categories.index', compact('categories', 'parentCategories', 'expenseAccounts'));
    }

    public function create()
    {
        $parentCategories = ExpenseCategory::all();
        $expenseAccounts = \App\Models\ChartOfAccount::where('type', 'expense')->get();
        return view('expenses.categories.create', compact('parentCategories', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        ExpenseCategory::create($validated);
        return redirect()->route('expense-categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(ExpenseCategory $expense_category)
    {
        $parentCategories = ExpenseCategory::where('id', '!=', $expense_category->id)->get();
        $expenseAccounts = \App\Models\ChartOfAccount::where('type', 'expense')->get();
        return view('expenses.categories.edit', compact('expense_category', 'parentCategories', 'expenseAccounts'));
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        // Prevent self-parenting
        if ($validated['parent_id'] == $expense_category->id) {
            $validated['parent_id'] = null;
        }

        $expense_category->update($validated);
        return redirect()->route('expense-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        $expense_category->delete();
        return redirect()->route('expense-categories.index')->with('success', 'Category deleted successfully.');
    }
}
