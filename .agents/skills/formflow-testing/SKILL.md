---
name: formflow-testing
description: "Write and run FormFlow Pest tests using its module layout, explicit factories, Basic authentication, database isolation and after-commit cache coverage. Use for project regression tests, endpoint tests and test failures."
---

# FormFlow Testing

Read [AGENTS.md](../../../AGENTS.md) and load `testing-best-practices` for coverage design,
assertions and isolation. This skill adds project-specific setup and test selection.
Confirm installed Pest/Laravel versions before using new APIs and use Boost `search-docs`
when syntax or framework behavior matters.

## Locate and create

Feature tests mirror module paths beneath `tests/Feature/Modules`.
Read the nearest test, `tests/Pest.php`, `tests/TestCase.php` and `phpunit.xml`.
The global Pest setup extends `Tests\TestCase` in Feature but does not enable
`RefreshDatabase`; choose the database trait explicitly in the test file.

Use `php artisan make:test --pest SomeBehaviorTest --no-interaction` for new tests.
Do not include `Feature/` in the test name. Inspect the generator's supported options
before creating a namespaced module test, and place the generated file in the matching
module directory. Pure logic without Laravel dependencies may use `--unit`.
Follow neighboring English behavior-oriented test names and existing dataset style.

## Database and fixtures

- Most endpoint tests use `LazilyRefreshDatabase`. Cache and observer tests requiring actual commits use `DatabaseMigrations`. Read those tests before selecting a trait; a surrounding rollback transaction can hide after-commit behavior.
- `phpunit.xml` selects database name `testing` and clears `DB_URL`, but does not choose `DB_CONNECTION`. Confirm the effective test configuration without printing secrets. Do not assume SQLite, change the developer's `.env`, or run a destructive reset against a non-test database to make a test pass.
- Use explicit `Database\Factories\*Factory::new()` calls, as the current tests do. Models live outside `App\Models` and do not uniformly use `HasFactory`; do not assume `Model::factory()` exists.
- `WorkspaceMembershipFactory` creates its related user and workspace. Use `for($user)` and `for($workspace)` to make intended relationships explicit. Create another independent workspace/member to exercise isolation rather than manually guessing IDs.
- The test cache store is `array`. Freeze or travel time for limiter/TTL assertions; do not sleep. Follow existing query-log cleanup patterns for query-count tests.

## Select valuable cases

For endpoint changes, exercise the actual middleware with `withBasicAuth($user->email, $password)`
and JSON requests. `actingAs` alone does not verify the public Basic-authentication contract.
Choose cases corresponding to changed decisions: successful requests, missing/invalid credentials,
non-members, wrong-parent resources, JSON-only input, PUT/PATCH differences and invalid fields.
Check database effects and unchanged state for rejected writes; assertions on status alone
do not prove a write was prevented.

For cache/observer changes, load `formflow-tenancy` and its cache reference. Warm the relevant
cache first, perform the write, then verify the next observable read across commit or rollback.
For public schema/example changes, include `tests/Feature/ApiDocumentationTest.php`.
Keep unrelated test repairs outside the task and never delete existing tests without approval.

## Run and report

Run the narrowest matching test file or filter from the repository root, for example:

```bash
php artisan test --compact tests/Feature/Modules/Tenancy/Infrastructure/Repository/CachedWorkspaceRepositoryTest.php
php artisan test --compact tests/Feature/ApiDocumentationTest.php
```

Rerun each test after changing it. If PHP files changed, run
`vendor/bin/pint --dirty --format agent`, inspect formatter changes and rerun the affected tests
if needed. Take account of pre-existing dirty PHP files so formatting does not silently add unrelated changes.
Report the exact checks and their outcomes. Explain environment failures separately from failing assertions.
After relevant feature tests pass, ask the user to run the complete suite with `php artisan test --compact`, as required by the root instructions.
