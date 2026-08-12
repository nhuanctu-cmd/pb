# Data ownership and classification

| Classification | Examples | Owner | Public by default |
|---|---|---|---:|
| GLOBAL PLATFORM | player passport, claims, official match, rating/ranking policy | platform authority | no |
| PLATFORM PUBLIC | public profile, official ranking, verified club, published tournament | platform | yes, privacy filtered |
| TENANT PRIVATE | booking, customer note, branch schedule, staff action | tenant | no |
| FINANCIAL | invoice, payment, wallet, subscription, POS | tenant/platform finance | no |
| RESTRICTED | credentials, identity evidence, fraud evidence, merge evidence | platform governance | no |
| AUDIT | actor, before/after, request ID, decision | platform/tenant audit | no |

Cross-tenant access is allowed only for platform-public projections or an explicit platform governance permission. A partner key cannot bypass player privacy, tenant isolation or restricted-data policy.

## Enforcement

`TenantDataPolicy` is the single policy boundary for `operational`, `platform_public`, `platform_network`, `platform_governance` and `restricted` data. Migration `375000` creates the tenant policy registry and `CommercialDemoSeeder` creates five idempotent defaults per active tenant. Operational queries must use an exact `tenant_id`; platform-public/network responses must additionally prove that the record is published.

The rebuild commands are tenant-aware:

- `php spark rating:rebuild --tenant=ID --dry-run`
- `php spark ranking:rebuild --tenant=ID --dry-run`

Queue rows receive `tenant_id` and an idempotency key in migration `376000`; webhook jobs are deduplicated before enqueue.

Operational support surfaces are tenant-scoped at `/admin/data-quality`, `/admin/governance` and `/admin/queue`. Queue retry/dead-letter actions require the current tenant context and never accept a job ID alone.

## Provenance minimum

Competitive records should answer: actor, source system, tenant, created time, verification level, policy/ruleset version, evidence reference and correction lineage.
