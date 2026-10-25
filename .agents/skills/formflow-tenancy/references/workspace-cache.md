# Workspace cache invariants

Read these files before modifying this workflow:

- `app/Modules/Tenancy/Infrastructure/Repository/CachedWorkspaceRepository.php`
- `app/Modules/Tenancy/Infrastructure/Cache/WorkspaceCacheInvalidator.php`
- `app/Modules/Tenancy/Infrastructure/Observers/WorkspaceObserver.php`
- `app/Modules/Tenancy/Infrastructure/Observers/WorkspaceMembershipObserver.php`
- `app/Providers/RepositoryServiceProvider.php` and `app/Providers/AppServiceProvider.php`

`WorkspaceRepositoryInterface` resolves to a cache decorator over `WorkspaceRepository`.
The current cache holds per-user attribute arrays, including empty results, for 600 seconds.
Reads hydrate fresh Eloquent objects: callers must not share a mutable cached model instance.
Data keys include a global workspace version and a per-user version.
Keep version-key construction in the existing repository/invalidator rather than duplicating strings.

Inside an open database transaction, the decorator bypasses shared cache reads and writes.
This allows transaction-local reads without publishing uncommitted data or corrupting the previous cached list on rollback.

Workspace updates and deletes invalidate the global version after commit through
`WorkspaceObserver`, which implements `ShouldHandleEventsAfterCommit`.
Membership create/delete invalidates the affected user's version. Membership updates
that change `user_id` or `workspace_id` invalidate the relevant old and new users through
an explicit `DB::afterCommit` callback. Unrelated users keep their cached lists.

Bulk query updates/deletes and quiet model writes can bypass model observers.
Inspect every proposed write path; either preserve eventful writes or implement and
test equivalent invalidation after commit. Do not replace version invalidation with `Cache::flush()`.

Use existing cache repository and observer tests as references. Cover the changed invariants:
fresh object hydration, cached empty lists, user isolation, expiration, committed writes,
rollback and transaction-local reads. Use `DatabaseMigrations` for tests that require
real commits, as those tests already do. Do not add a surrounding test transaction that
prevents after-commit callbacks from executing. Scope query logging to the read being
measured and restore logging state when finished.
