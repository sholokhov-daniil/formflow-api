---
name: formflow-api-contract
description: "Change FormFlow HTTP endpoints and keep routes, JSON validation, Resources, error responses, OpenAPI and contract tests aligned. Use for public API behavior or docs/api/openapi.yaml changes."
---

# FormFlow API Contract

Read [AGENTS.md](../../../AGENTS.md), the affected route and its feature tests first.
Apply `laravel-best-practices` when changing PHP. Use installed-version Boost docs
for framework syntax; the rules below describe this application's current behavior.

## Contract sources

- `routes/api.php`: `/api/v1`, named resource routes, authentication and per-action authorization.
- `app/Modules/<Module>/Presentation/Http`: controllers, JSON Form Requests and list/detail Resources.
- `bootstrap/app.php`: API JSON exception rendering, including the custom 404 response.
- `docs/api/openapi.yaml`: OpenAPI 3.1 contract and examples.
- `tests/Feature/ApiDocumentationTest.php`: examples, schemas, authentication and documentation delivery checks.
- `tests/Feature/Modules/<Module>/Presentation/Http/Controller`: endpoint behavior and database effects.

For a mismatch, determine whether the requested change concerns behavior or documentation.
Do not silently rewrite runtime behavior to agree with stale documentation.

## Preserve the relevant behavior

- Protected routes currently use `ThrottleAuthenticationFailures` before `auth.basic`, then action-specific `can` middleware. Keep policies active on the actual route; a policy class alone does not protect an endpoint.
- Basic credentials use the user's email and password. Keep authentication, authorization and rate limiting distinct. The current limiter keys by IP and Basic username; the third failure within three hours starts a three-hour block. A blocked request does not extend that block. Successful authentication before a block resets failures, even if the policy then returns 403. Read the limiter and its tests before changing these rules.
- Nested projects use scoped bindings under workspace. Load `formflow-tenancy` for changes to nesting, membership or access checks.
- Existing write requests validate JSON bodies only. Read `validationData()`, `rules()`, `after()` and `toData()` together. Query strings and form data must not become a back door for write fields.
- Preserve PUT versus PATCH presence rules. Distinguish an omitted field from an explicitly supplied `null`; inspect the DTO and repository before claiming a nullable field can be cleared. If the desired behavior is unspecified, clarify it instead of choosing a new contract.
- Keep IDs of parents and authenticated users server-controlled. A body field must not move a resource to another workspace unless that operation is explicitly requested and authorized.
- List and detail Resources intentionally expose different fields. Preserve the `data` envelope, empty-list shape and existing ordering. Inspect the affected endpoint before adding pagination or fields.
- Follow current success codes: reads/updates 200, creation 201, deletion 204 with no body. Document applicable 401, 403, 404, 422 and 429 outcomes from observed behavior, including `WWW-Authenticate` and `Retry-After` where applicable.

## Update and verify

Change the route, Request/DTO, repository behavior and Resource only as needed by the task.
Update the corresponding OpenAPI operation, schemas and examples in the same change;
document only implemented behavior. Keep operation IDs unique and local `$ref` targets valid.

Use `formflow-testing` to exercise representative requests, including validation and access
failures. Run the affected controller tests and `tests/Feature/ApiDocumentationTest.php`.
The existing documentation tests cover selected contracts; they are not a complete OpenAPI validator.
If changing documentation delivery, also inspect `routes/web.php` and `resources/views/doc/api.blade.php`.

Resolve any URL shared with the user through Boost `get-absolute-url`.
