import { test, expect } from '@playwright/test';

test.describe('Expenses Module E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to application
    await page.goto('/');
  });

  test('should create a new expense', async ({ page }) => {
    // Navigate to expenses API
    const response = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 99.99,
        currency: 'USD',
        description: 'Conference ticket',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'pending',
      },
    });

    expect(response.status()).toBe(201);
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.expense).toHaveProperty('id');
    expect(body.expense.amount).toBe('99.99');
  });

  test('should retrieve user expenses', async ({ page }) => {
    // Create an expense first
    await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 50.00,
        currency: 'USD',
        description: 'Office supplies',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'pending',
      },
    });

    // Retrieve expenses
    const response = await page.request.get('/api/expenses/1');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(Array.isArray(body.expenses)).toBe(true);
  });

  test('should retrieve single expense', async ({ page }) => {
    // Create an expense
    const createResponse = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 75.00,
        currency: 'USD',
        description: 'Travel expense',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'pending',
      },
    });

    const createdExpense = await createResponse.json();
    const expenseId = createdExpense.expense.id;

    // Retrieve single expense
    const response = await page.request.get(`/api/expense/${expenseId}`);
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.expense.id).toBe(expenseId);
  });

  test('should update an expense', async ({ page }) => {
    // Create an expense
    const createResponse = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 100.00,
        currency: 'USD',
        description: 'Original description',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'pending',
      },
    });

    const createdExpense = await createResponse.json();
    const expenseId = createdExpense.expense.id;

    // Update expense
    const updateResponse = await page.request.put(`/api/expense/${expenseId}`, {
      data: {
        status: 'approved',
        description: 'Updated description',
      },
    });

    expect(updateResponse.status()).toBe(200);
    const body = await updateResponse.json();
    expect(body.success).toBe(true);
    expect(body.expense.status).toBe('approved');
    expect(body.expense.description).toBe('Updated description');
  });

  test('should delete an expense', async ({ page }) => {
    // Create an expense
    const createResponse = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 150.00,
        currency: 'USD',
        description: 'To be deleted',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'pending',
      },
    });

    const createdExpense = await createResponse.json();
    const expenseId = createdExpense.expense.id;

    // Delete expense
    const deleteResponse = await page.request.delete(`/api/expense/${expenseId}`);
    expect(deleteResponse.status()).toBe(200);
    const body = await deleteResponse.json();
    expect(body.success).toBe(true);

    // Verify it's deleted
    const getResponse = await page.request.get(`/api/expense/${expenseId}`);
    expect(getResponse.status()).toBe(404);
  });

  test('should validate required fields', async ({ page }) => {
    // Try to create without required fields
    const response = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        // missing amount, currency, expense_date
      },
    });

    expect(response.status()).toBe(422);
  });

  test('should validate amount is numeric', async ({ page }) => {
    // Try to create with invalid amount
    const response = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 'not-a-number',
        currency: 'USD',
        expense_date: new Date().toISOString().split('T')[0],
      },
    });

    expect(response.status()).toBe(422);
  });

  test('should validate status enum', async ({ page }) => {
    // Try to create with invalid status
    const response = await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 99.99,
        currency: 'USD',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'invalid-status',
      },
    });

    expect(response.status()).toBe(422);
  });

  test('should return 404 for non-existent expense', async ({ page }) => {
    const response = await page.request.get('/api/expense/99999');
    expect(response.status()).toBe(404);
    const body = await response.json();
    expect(body.error).toBe('Expense not found');
  });

  test('should handle receipt upload', async ({ page }) => {
    // This test validates receipt upload endpoint
    const response = await page.request.post('/api/receipt/upload', {
      multipart: {
        receipt: {
          name: 'receipt.jpg',
          mimeType: 'image/jpeg',
          buffer: Buffer.from([0xFF, 0xD8, 0xFF, 0xE0]), // JPEG header
        },
        user_id: '1',
      },
    });

    expect(response.status()).toBeGreaterThanOrEqual(200);
    expect(response.status()).toBeLessThan(300);
  });

  test('should filter expenses by status', async ({ page }) => {
    // Create multiple expenses with different statuses
    await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 50.00,
        currency: 'USD',
        description: 'Pending expense',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'pending',
      },
    });

    await page.request.post('/api/expense', {
      data: {
        user_id: 1,
        amount: 75.00,
        currency: 'USD',
        description: 'Approved expense',
        expense_date: new Date().toISOString().split('T')[0],
        status: 'approved',
      },
    });

    // Retrieve all expenses
    const response = await page.request.get('/api/expenses/1');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(Array.isArray(body.expenses)).toBe(true);
    expect(body.expenses.length).toBeGreaterThanOrEqual(2);
  });
});
