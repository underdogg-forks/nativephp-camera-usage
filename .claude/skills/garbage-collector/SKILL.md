---
name: garbage-collector
description: Identify and improve or delete tests that don't exercise real application code
type: research-and-fix
difficulty: medium
tags: [testing, code-quality, php, phpunit, pest]
---

# Garbage Collector — Test Quality Filter

## Purpose

Automatically identify tests that don't exercise real application code, report findings, and either:
1. **Improve** the test to use real dependencies
2. **Delete** the test if it's not valuable

## What this detects

### Pattern 1: PHP Native Function Testing
Tests that only verify language behavior, not application logic:
```php
#[Test]
public function it_checks_if_string_is_string(): void {
    $this->assertTrue(is_string('hello'));  // ❌ Testing PHP, not your code
}
```

### Pattern 2: Obvious Assertion Testing
Tests where the assertion is a tautology:
```php
#[Test]
public function it_verifies_true_is_true(): void {
    $this->assertTrue(true);  // ❌ Always passes
}

#[Test]
public function it_verifies_array_is_array(): void {
    $this->assertTrue(is_array([]));  // ❌ Language built-in
}
```

### Pattern 3: No Dependencies Tests
Tests with no Service, Model, or Factory usage:
```php
#[Test]
public function it_does_something(): void {
    // No setup, no mocks, no real objects
    $result = 1 + 1;
    $this->assertEquals(2, $result);  // ❌ Not testing your app
}
```

### Pattern 4: Mock-Only Tests
Tests that create fakes but never call real code:
```php
#[Test]
public function it_tests_nothing(): void {
    $mock = $this->mock(SomeService::class);
    $mock->shouldReceive('method')->andReturn('value');
    // Never actually uses $mock
    $this->assertTrue(true);
}
```

### Pattern 5: Single Assertion Tests
Tests with only one assertion that verify language features:
```php
#[Test]
public function it_verifies_count(): void {
    $this->assertCount(2, [1, 2]);  // ❌ Testing PHP array behavior
}
```

## How it works

1. **Scan** test files for patterns matching the garbage categories
2. **Report** findings with file, line number, and reason
3. **Suggest** improvement or deletion
4. **Implement** if user approves:
   - **Improve**: Rewrite to use real application code (Models, Services, Factories)
   - **Delete**: Remove if not valuable

## Improvements vs Deletions

### Keep and Improve ✅
- Tests that CAN be fixed to exercise real code
- Tests for important domain logic
- Tests with good test names (hint at intent)

### Delete ❌
- Tests that verify PHP native functions
- Tests that will never exercise application code
- Tests with tautology assertions
- Tests added just for coverage metrics

## Usage

```bash
# Scan for garbage tests (no changes)
claude --skill garbage-collector

# With specific directory (optional)
claude --skill garbage-collector tests/Feature/

# Review findings, then user approves changes
```

## Examples

### Example 1: Garbage Test (Delete)
```php
// ❌ BEFORE
#[Test]
public function it_verifies_is_int_with_integer(): void {
    $this->assertTrue(is_int(42));
}
```
**Action**: Delete — tests PHP, not your code.

---

### Example 2: Weak Test (Improve)
```php
// ❌ BEFORE
#[Test]
public function it_handles_user_creation(): void {
    $user = new User();
    $this->assertNotNull($user);
}
```

```php
// ✅ AFTER
#[Test]
public function it_persists_user_to_database(): void {
    $user = User::factory()->create();
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => $user->email,
    ]);
}
```
**Action**: Improve — now exercises real Model, Factory, and database.

---

### Example 3: Strong Test (Keep)
```php
// ✅ Already good
#[Test]
public function it_validates_expense_amount(): void {
    $expense = Expense::factory()->create(['amount' => -100]);
    
    $this->assertTrue($expense->amount < 0);  // Real state, real code
}
```
**Action**: Keep — exercises real Model, Factory, and validation.

---

## Configuration

No configuration needed. The skill:
- Scans all test files in `tests/`
- Identifies patterns automatically
- Reports with severity (critical, warning, info)
- Suggests exact changes

## Common patterns to improve

| Pattern | Fix |
|---------|-----|
| `$this->assertTrue(is_string(...))` | Use `assertIsString()` or test string method |
| `$this->assertTrue(is_array(...))` | Use `assertIsArray()` or test collection logic |
| `$this->assertEquals(2, 1+1)` | Delete or test real arithmetic code |
| No Model/Factory usage | Add `Model::factory()->create()` |
| No assertions about state | Assert database, relationships, properties |

## Exit criteria

All remaining tests:
- ✅ Exercise real application code
- ✅ Use Models, Services, or Factories
- ✅ Have meaningful assertions
- ✅ Follow Arrange-Act-Assert pattern
- ✅ Would fail if application code was broken

## See also

- `test-honesty` — Validate factory/schema alignment
- `test-gaps` — Find missing test coverage
- `pest-control` — Code quality improvements
