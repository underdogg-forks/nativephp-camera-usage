# Expenses Module REST API Documentation

Complete REST API with Bearer token (Sanctum) authentication for the Expenses module. All endpoints require a valid access token.

## Authentication

### Get Access Token

```bash
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "token": "1|abc123..."
}
```

### Using the Token

All subsequent requests must include:
```
Authorization: Bearer {token}
```

## API Endpoints

### Expenses

#### List User Expenses
```
GET /api/expenses
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "expenses": [
    {
      "id": 1,
      "user_id": 1,
      "category_id": 1,
      "expense_number": "EXP-00001",
      "expense_type": "one_time",
      "expense_status": "draft",
      "expense_amount": 99.99,
      "currency": "USD",
      "description": "Conference ticket",
      "receipt_path": "receipts/2026/09/05/...",
      "expensed_at": "2026-09-05T10:30:00Z",
      "created_at": "2026-09-05T10:30:00Z",
      "updated_at": "2026-09-05T10:30:00Z"
    }
  ]
}
```

#### Get Single Expense
```
GET /api/expenses/{id}
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "expense": {
    "id": 1,
    "user_id": 1,
    "category_id": 1,
    "expense_number": "EXP-00001",
    "expense_type": "one_time",
    "expense_status": "draft",
    "expense_amount": 99.99,
    "currency": "USD",
    "description": "Conference ticket",
    "receipt_path": "receipts/2026/09/05/...",
    "expensed_at": "2026-09-05T10:30:00Z",
    "created_at": "2026-09-05T10:30:00Z",
    "updated_at": "2026-09-05T10:30:00Z",
    "category": {
      "id": 1,
      "category_name": "Travel",
      "description": "...",
      "created_at": "...",
      "updated_at": "..."
    }
  }
}
```

#### Create Expense
```
POST /api/expenses
Authorization: Bearer {token}
Content-Type: application/json

{
  "category_id": 1,
  "expense_number": "EXP-00002",
  "expense_type": "one_time",
  "expense_amount": 50.00,
  "currency": "USD",
  "description": "Lunch meeting",
  "expensed_at": "2026-09-05T12:00:00Z",
  "receipt_path": "receipts/2026/09/05/abc123.jpg"
}

Response: 201 Created
{
  "success": true,
  "expense": { /* full expense object */ }
}

Validation Errors (422):
{
  "message": "The team field is required. (and 2 more errors)",
  "errors": {
    "expense_number": ["The expense number field is required."],
    "expense_type": ["The expense type field is required."],
    "expense_amount": ["The expense amount field is required."]
  }
}
```

#### Update Expense
```
PUT /api/expenses/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "expense_status": "submitted",
  "description": "Updated description"
}

Response: 200 OK
{
  "success": true,
  "expense": { /* updated expense object */ }
}
```

#### Delete Expense
```
DELETE /api/expenses/{id}
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true
}
```

### Expense Workflow

#### Approve Expense
```
PUT /api/expenses/{id}/approve
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "expense": {
    "id": 1,
    "expense_status": "approved",
    /* ... other fields */
  }
}
```

#### Reject Expense
```
PUT /api/expenses/{id}/reject
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "expense": {
    "id": 1,
    "expense_status": "draft",
    /* ... other fields */
  }
}
```

## Status Codes

| Code | Meaning |
|------|---------|
| 200  | Success |
| 201  | Created |
| 400  | Bad Request |
| 401  | Unauthorized (missing/invalid token) |
| 403  | Forbidden (user doesn't own resource) |
| 404  | Not Found |
| 422  | Validation Failed |
| 500  | Server Error |

## Enums

### Expense Type
- `fixed` - Fixed recurring expense
- `one_time` - Single occurrence
- `recurring` - Repeating pattern

### Expense Status
- `draft` - Initial state, not yet submitted
- `submitted` - Submitted for review
- `approved` - Approved and ready
- `reimbursed` - Reimbursement processed
- `billed` - Billed to client
- `paid` - Payment received/made

## Test Coverage

✅ **12 API Authentication Tests** (57 assertions)
- Bearer token requirement
- CRUD operations
- User isolation (authorization)
- Expense approval workflow
- Form validation
- Required field validation
- Numeric amount validation
- Enum value validation

Run tests:
```bash
php artisan test Modules/Expenses/Tests/Feature/ExpenseApiAuthTest.php --testdox
```

## Usage Example (Mobile App)

```javascript
// Get token
const response = await fetch('/api/login', {
  method: 'POST',
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});
const { token } = await response.json();

// Create expense with photo
const expenseResponse = await fetch('/api/expenses', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    category_id: 1,
    expense_number: `EXP-${Date.now()}`,
    expense_type: 'one_time',
    expense_amount: 45.50,
    currency: 'USD',
    description: 'Lunch with client',
    expensed_at: new Date().toISOString(),
    receipt_path: 'receipts/2026/09/05/photo.jpg'
  })
});
const expense = await expenseResponse.json();

// Approve expense
const approveResponse = await fetch(`/api/expenses/${expense.id}/approve`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```
