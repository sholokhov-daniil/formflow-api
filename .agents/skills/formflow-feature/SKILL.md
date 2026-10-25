---
name: formflow-feature
description: "Implement FormFlow API features across module layers, DTOs, repository contracts and provider bindings. Use for a new use case or a change spanning modules; skip isolated copy, styling and infrastructure deployment tasks."
---

# FormFlow Feature Development

Use this workflow to deliver a complete change within the existing module structure.
Read [AGENTS.md](../../../AGENTS.md) and any matching `.ai/rules` before editing.
Apply `laravel-best-practices` for the affected PHP concerns.

## Trace the use case

Start with the relevant route, controller and test. Follow only the dependencies the change touches.
Use the workspace implementation in `app/Modules/Tenancy` as the established example;
compare `FormDefinition` when working with nested resources.

| Concern | Existing location |
| --- | --- |
| HTTP validation and request-to-DTO conversion | `Presentation/Http/Requests`, using `toData()` |
| Public response fields | `Presentation/Http/Resources` |
| HTTP orchestration | `Presentation/Http/Controller` (singular) |
| Input data | `Application/DTO`, usually `final readonly` |
| Persistence contracts | `Application/Contracts/Repository` |
| Queries and writes | `Infrastructure/Repository` |
| Eloquent models and relationships | `Infrastructure/Eloquent` |
| Authorization | `Infrastructure/Policies` |
| Domain values | `Domain`, such as `WorkspaceKind` and `FieldType` |

Use paths relative to the selected `app/Modules/<Module>`. Keep existing cross-module
dependencies unless the requested change needs a different boundary. Do not introduce
commands, buses, domain entities or extra service layers solely to match a theoretical architecture.

## Implement the complete path

1. Identify the public behavior and affected decisions. Resolve missing business rules with the user; do not invent ownership roles, publishing states or submission rules.
2. Read the current schema with Boost `database-schema` before model or migration work. Confirm package versions and use Boost `search-docs` for framework APIs that the change depends on.
3. Use the appropriate Artisan generator with `--no-interaction` and an explicit module namespace/path. Inspect `--help` before choosing options so generated files land in the established directories.
4. Keep input conversion in Form Requests, persistence in the existing repositories and serialization in Resources. Pass validated DTOs, authenticated identity and bound parent IDs to write methods.
5. Bind new repository contracts in `app/Providers/RepositoryServiceProvider.php`. Preserve decorators: `WorkspaceRepositoryInterface` resolves through `CachedWorkspaceRepository`. Register policies and observers in `AppServiceProvider`; use `PresentationServiceProvider` for middleware construction dependencies.
6. Keep related writes atomic, as in workspace creation together with its membership. If writes affect workspace visibility or cached data, load `formflow-tenancy` before changing their path.
7. For public behavior, load `formflow-api-contract`. For tests, load `formflow-testing` and select coverage around the changed decisions.

## Completion

Check the whole dependency path, including provider registration and authorization.
Run the relevant tests and the required formatter when PHP changes. Report the behavior,
validation results and any deliberately unresolved product questions. Keep unrelated cleanup outside the change.
