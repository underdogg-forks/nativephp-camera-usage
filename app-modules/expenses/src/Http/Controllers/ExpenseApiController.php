<?php

namespace Modules\Expenses\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Expenses\Http\Requests\StoreExpenseRequest;
use Modules\Expenses\Http\Requests\UpdateExpenseRequest;
use Modules\Expenses\Models\Expense;

class ExpenseApiController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $request->user()->expenses()->create([
            'category_id' => $request->category_id,
            'expense_number' => $request->expense_number,
            'expense_type' => $request->expense_type,
            'expense_status' => ExpenseStatus::DRAFT->value,
            'expense_amount' => $request->expense_amount,
            'currency' => $request->currency,
            'description' => $request->description,
            'expensed_at' => $request->expensed_at,
            'receipt_path' => $request->receipt_path,
        ]);

        return response()->json([
            'success' => true,
            'expense' => $expense,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $expenses = $request->user()->expenses()
            ->with('category')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'expenses' => $expenses,
        ]);
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        return response()->json([
            'success' => true,
            'expense' => $expense->load('category'),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $expense->update($request->validated());

        return response()->json([
            'success' => true,
            'expense' => $expense->refresh(),
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return response()->json(['success' => true]);
    }

    public function approve(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $expense->update(['expense_status' => ExpenseStatus::APPROVED->value]);

        return response()->json([
            'success' => true,
            'expense' => $expense->refresh(),
        ]);
    }

    public function reject(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $expense->update(['expense_status' => ExpenseStatus::DRAFT->value]);

        return response()->json([
            'success' => true,
            'expense' => $expense->refresh(),
        ]);
    }
}
