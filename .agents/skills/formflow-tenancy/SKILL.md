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

Use [the Tenancy business flow](../../../docs/business-logic/tenancy.md) for the canonical
access and membership scenario, [OpenAPI](../../../docs/api/openapi.yaml) for endpoint-level
outcomes, and [architecture](../../../docs/architecture.md) for module boundaries. Keep this
skill focused on implementation safeguards: enforce both parent-scoped binding and policies,
derive identity and parent IDs from the authenticated request and route, and preserve typed IDs.
For membership and workspace writes, check dependent visibility and cache invalidation.

## Module boundary and current access decisions

For new consumers of Tenancy, expose a public Application contract returning a decision or
IDs/DTOs without Eloquent models. Existing membership repository consumers and cross-module
ORM queries are current exceptions; do not copy them into new integrations. Apply
`formflow-architecture` when changing that boundary and preserve query isolation as well as policies.

Authorization needs a current membership decision. Do not replace it with an asynchronous
membership projection without an explicitly agreed revocation model. Membership-change events
may drive independent reactions after commit, but do not replace the access decision itself.
For a boundary change, cover access after membership revocation and the affected cache lifecycle.

## Cache changes

When a change touches cached workspace data, membership writes, workspace updates/deletes,
observers or transaction boundaries, read [references/workspace-cache.md](references/workspace-cache.md)
before editing. Do not apply this cache design to unrelated entities without a task-specific need.

## Verification

Use `formflow-testing` to select the affected controller, policy, repository and observer tests.
Test a member, a non-member and a resource belonging to another workspace where applicable.
For mutations, assert that rejected requests leave persisted data unchanged. For cache changes,
also verify visibility after commit and after rollback.
