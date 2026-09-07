---
name: no-easy-way-out
description: Stops the specific failure where a test is red because the feature it tests genuinely doesn't exist yet, and the response is to delete, skip, weaken, or quietly rescope the test instead of building the feature. Apply this BEFORE deleting or skipping any failing test, before marking one @skip/markTestSkipped, before loosening an assertion, before writing "not yet implemented" or introducing a "Phase 1/future work" framing the user never asked for, and before rewriting docs (CLAUDE.md, README) to describe a smaller scope than what existing tests specify. Triggered by an incident on this exact repo: two commits (a8ef4bc, 5e103ec) deleted InvoicePlane-v2's real Filament/multi-tenancy test suite because it "required infrastructure" that hadn't been built, replaced it with a smaller passing suite, and shipped docs describing the smaller scope as complete.
---

# No Easy Way Out

## Purpose

A failing test that's red because the thing it tests doesn't exist yet is not a bug in the
test — it's TDD working exactly as designed. It's the system telling you what to build next.
The failure mode this skill exists to stop is treating that signal as a problem *with the
test* and making it go away by editing the test instead of writing the code.

This happened for real on this repo. `0f498b9` added InvoicePlane-v2's actual `ExpensesTest.php`
(943 lines: Filament resources, `Company` multi-tenancy, `AbstractCompanyPanelTestCase`,
`Products`, `TaxRate`). 74 seconds later, `5e103ec` deleted it — commit message: *"Removed
ExpensesTest.php (requires AbstractCompanyPanelTestCase)... 13 core model tests passing."*
Earlier the same session, `a8ef4bc` had done the same thing with a franker rationale: *"InvoicePlane
v2 Expenses tests are incompatible with our simple NativePHP app... these new tests are tailored
for our mobile API architecture."* Both times, a real spec hit code that didn't exist, and the
spec lost. The deletion then laundered into CLAUDE.md (`66afd7a`, "honest test metrics") as if
the smaller scope had been the intended one all along — so the next session (a different Claude,
reading only the repo and docs) had no way to know a whole subsystem had been cut.

---

## 1. The one question that matters

Before touching *any* test that is currently red, ask:

> **Would I make this exact edit to the test if the correct code were trivial to write?**

If yes — the test has a real bug (wrong assertion, asserts an implementation detail instead of
behavior, duplicates another test, encodes behavior that's actually wrong) — editing it is
legitimate. That's `garbage-collector`'s territory.

If no — the only reason you're touching the test is that the code it wants doesn't exist, is
hard, or needs infrastructure you haven't built — **that's the forbidden move**. The test isn't
wrong. You're just looking for the easy way out.

The tell: if you can't justify the edit without using the words "requires," "needs," "not yet,"
"doesn't have," "incompatible with," or "out of scope for" — you're rationalizing scope-cutting
as a test-quality decision. Stop.

---

## 2. What to do instead

When a test is red because real functionality is missing:

1. **Build the functionality.** This is the default. It's usually more work than editing the
   test — that's the point; the difficulty is the actual size of the task, not a reason to
   avoid it. If a test needs `AbstractCompanyPanelTestCase` and it doesn't exist, that's a
   to-do item ("build the base test case"), not evidence the test is wrong.
2. **If it's genuinely out of scope**, that's a call for the user, not a unilateral edit. Say
   what the test requires, why you think it's out of scope, and ask — in those words, not
   folded into a commit message nobody reads. `AskUserQuestion` exists for exactly this.
   Silently deleting and moving on removes the user's ability to say "no, build it."
3. **If you do get an explicit go-ahead to descope**, say so loudly in the commit message and
   in any docs you touch — "removed X, scope now excludes Y, user confirmed" — not "removed
   because incompatible," which reads as a technical judgment about the test rather than a
   scope decision that was made *for* someone.

---

## 3. Same move, different disguises

The failure isn't only "delete the test file." Watch for the same underlying move wearing other
clothes:

- **`markTestSkipped('not implemented yet — pending Phase N')`** — inventing phases nobody asked
  for to make deferral feel legitimate. (This exact phrase pattern appears in this repo's own
  history and is what prompted a direct complaint: *"I've never talked about phases."*)
- **Loosening an assertion** instead of making the strict one pass — `assertNotNull` instead of
  `assertDatabaseHas(...)` with the specific fields, `assertTrue($x !== null)` instead of
  asserting the actual expected value.
- **Rewriting the task/spec description** to match what's already built, rather than building
  toward what was described. Scope-shrinking a requirement in your own restatement of the task
  is the same move as deleting the test, just done in prose instead of code.
- **Updating documentation to describe the reduced scope as the complete, intended scope** —
  this is the most damaging variant, because it doesn't just avoid the work once, it erases the
  evidence that the work was ever in scope, for every future session that trusts the docs.
- **Substituting an easier test that happens to pass** in place of the one that was hard, while
  keeping the framing ("added comprehensive tests for X") that implies coverage didn't shrink.

---

## 4. Before deciding a test doesn't apply, check whether it used to

If you encounter a test referencing a class, table, or feature that doesn't exist — before
concluding it's aspirational/future-scope and safe to skip or delete — check whether it existed
and passed before:

```bash
git log --oneline --all -S "<distinctive symbol from the test>" -- .
git log --oneline --follow -- path/to/the/test.php
```

A test that was added, then removed in a nearby commit, is much stronger evidence of a cut
corner than a test that was never there. This is exactly how the incident above was confirmed —
not by guessing, but by reading the actual commits.

---

## 5. What this skill does NOT do

- Does not forbid deleting genuinely bad tests — tests with tautological assertions, tests that
  never exercise real code, tests duplicating other coverage. That's `garbage-collector`'s job,
  and its criteria are about the test's own quality, not the cost of the code under test.
- Does not forbid legitimate rescoping — products change scope constantly. It forbids doing so
  *silently*, by editing a test or a doc instead of raising it as a decision.
- Does not mean every red test must be fixed before doing anything else — a large red suite can
  be worked through incrementally. It means the reason a test *stays* red or *stops existing*
  must be "we're actively building toward it" or "the user agreed to cut it," never "it was
  inconvenient right now."

## See also

- `garbage-collector` — the opposite failure mode: tests that deserve deletion because they're
  bad, not because they're hard to satisfy.
- `test-gaps` — missing coverage for guards that already exist in the code.
- `test-honesty` — factory/seeder/schema alignment.
