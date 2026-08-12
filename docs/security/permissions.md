# Security and permission model

## Layers

1. Authentication: session for web, signed bearer for internal API, scoped partner key for partner API.
2. Tenant context: every tenant-private query and write must resolve tenant from trusted context.
3. Authorization: permission/action check at route and service boundary.
4. Entitlement: subscription feature/usage check, separate from RBAC.
5. Data policy: public/member/private/restricted projection filtering.
6. Audit: actor, tenant, entity, before/after, request ID and reason for sensitive mutations.

## Mandatory tests

- cross-tenant booking/payment/player access;
- partner key scope escalation and revoked/expired key;
- public player privacy and QR tampering/expiry;
- official result authorization and correction lineage;
- rating/ranking idempotency and compensation;
- webhook signature/replay/dead-letter;
- API rate limit and ID enumeration;
- credential encryption and consent before external sync.

## Forbidden patterns

- exposing ORM rows directly from public APIs;
- using tenant session fallback for platform-global public reads without a policy;
- storing provider credentials or API secrets in plaintext;
- treating rating as ranking, or modifying official result in place;
- using GET for destructive operations;
- using Vietnamese display text, VND or UTC+7 as domain keys.
