---
name: formflow-tenancy
description: "Maintain FormFlow workspace membership, cross-workspace isolation, nested resource authorization and workspace-list cache consistency. Use when changing access policies, membership queries, observers or cached workspace reads and writes."
---

# FormFlow Tenancy

Read [AGENTS.md](../../../AGENTS.md) and the affected access tests.
Use `laravel-best-practices` for security, queries and caching as applicable.

## Access path

Inspect these implementations before extending them:

- `Tenancy/Infrastructure/Policies/WorkspacePolicy.php`
- `Tenancy/Application/Contracts/Repository/WorkspaceMembershipRepositoryInterface.php`
- `Tenancy/Infrastructure/Repository/WorkspaceRepository.php`
- `FormDefinition/Infrastructure/Policies/ProjectPolicy.php`
- `FormDefinition/Infrastructure/Repository/ProjectWorkspaceRepository.php`
- `routes/api.php` and policy registrations in `app/Providers/AppServiceProvider.php`

Module paths above are relative to `app/Modules`.

Current workspace access is membership-based. `personal` and `team` are workspace kinds;
there is no separate owner/admin role in the current policies. Do not infer elevated
permissions from a kind, creator or guessed role.

Workspace lists filter by the authenticated user's memberships. Project lists constrain
both the bound workspace and that user's membership. Preserve these filters even when
route-level policies also check access. Pass identity from the authenticated request,
and the parent ID from route binding rather than trusting write payloads.

Workspace UUIDs and user integer IDs are different types. Preserve these types in DTOs,
contracts, cache keys and query parameters.

For nested resources, maintain both scoped parent-child binding and policy authorization.
A project under the wrong workspace currently returns 404; an existing project under
its correct workspace returns 403 for a non-member. Preserve the tested distinctions.
Ensure `Workspace::projects()` and the nested route agree when extending resource binding.

Workspace creation writes the workspace and initial membership in one transaction.
For membership mutations, check downstream read visibility and cache invalidation as well as the row being changed.

## Cache changes

When a change touches cached workspace data, membership writes, workspace updates/deletes,
observers or transaction boundaries, read [references/workspace-cache.md](references/workspace-cache.md)
before editing. Do not apply this cache design to unrelated entities without a task-specific need.

## Verification

Use `formflow-testing` to select the affected controller, policy, repository and observer tests.
Test a member, a non-member and a resource belonging to another workspace where applicable.
For mutations, assert that rejected requests leave persisted data unchanged. For cache changes,
also verify visibility after commit and after rollback.
