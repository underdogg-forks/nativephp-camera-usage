<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Expenses\Models\Expense;

/*
|--------------------------------------------------------------------------
| Mobile Routes
|--------------------------------------------------------------------------
|
| Routes for NativePHP mobile application - receipt capture and expense tracking
|
*/

// Receipt upload and storage
Route::post('/api/receipt/upload', function (Request $request) {
    $request->validate([
        'receipt' => 'required|image|mimes:jpeg,png,gif,webp|max:10240',
        'user_id' => 'required|integer',
    ]);

    if (!$request->hasFile('receipt')) {
        return response()->json(['error' => 'No receipt file provided'], 400);
    }

    try {
        $file = $request->file('receipt');
        $path = Storage::disk('expenses')->putFile(
            'receipts/' . now()->format('Y/m/d'),
            $file
        );

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('expenses')->url($path),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to upload receipt: ' . $e->getMessage()], 500);
    }
})->name('receipt.upload');

// Retrieve receipt file
Route::get('/api/receipt/{path}', function (Request $request, $path) {
    $fullPath = 'receipts/' . $path;

    if (!Storage::disk('expenses')->exists($fullPath)) {
        return response('Not found', 404);
    }

    try {
        return response()->file(
            Storage::disk('expenses')->path($fullPath),
            ['Content-Type' => Storage::disk('expenses')->mimeType($fullPath)]
        );
    } catch (\Exception $e) {
        return response('Failed to retrieve receipt', 500);
    }
})->name('receipt.show');

// Create expense with receipt
Route::post('/api/expense', function (Request $request) {
    $request->validate([
        'user_id' => 'required|integer',
        'amount' => 'required|numeric|min:0',
        'currency' => 'sometimes|string|max:3',
        'receipt_path' => 'nullable|string',
        'description' => 'nullable|string|max:255',
        'expense_date' => 'required|date',
        'status' => 'sometimes|in:pending,approved,rejected',
    ]);

    try {
        $expense = Expense::create($request->only([
            'user_id',
            'amount',
            'currency',
            'receipt_path',
            'description',
            'expense_date',
            'status',
        ]));

        return response()->json([
            'success' => true,
            'expense' => $expense,
        ], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to create expense: ' . $e->getMessage()], 500);
    }
})->name('expense.store');

// Get user's expenses
Route::get('/api/expenses/{userId}', function ($userId) {
    try {
        $expenses = Expense::where('user_id', $userId)
            ->orderBy('expense_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'expenses' => $expenses,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to retrieve expenses: ' . $e->getMessage()], 500);
    }
})->name('expense.list');

// Get single expense
Route::get('/api/expense/{id}', function ($id) {
    try {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json(['error' => 'Expense not found'], 404);
        }

        return response()->json([
            'success' => true,
            'expense' => $expense,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to retrieve expense: ' . $e->getMessage()], 500);
    }
})->name('expense.show');

// Update expense
Route::put('/api/expense/{id}', function (Request $request, $id) {
    $request->validate([
        'amount' => 'sometimes|numeric|min:0',
        'currency' => 'sometimes|string|max:3',
        'description' => 'nullable|string|max:255',
        'expense_date' => 'sometimes|date',
        'status' => 'sometimes|in:pending,approved,rejected',
    ]);

    try {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json(['error' => 'Expense not found'], 404);
        }

        $expense->update($request->only([
            'amount',
            'currency',
            'description',
            'expense_date',
            'status',
        ]));

        return response()->json([
            'success' => true,
            'expense' => $expense,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to update expense: ' . $e->getMessage()], 500);
    }
})->name('expense.update');

// Delete expense
Route::delete('/api/expense/{id}', function ($id) {
    try {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json(['error' => 'Expense not found'], 404);
        }

        // Delete associated receipt file if it exists
        if ($expense->receipt_path && Storage::disk('expenses')->exists($expense->receipt_path)) {
            Storage::disk('expenses')->delete($expense->receipt_path);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete expense: ' . $e->getMessage()], 500);
    }
})->name('expense.destroy');
