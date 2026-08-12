# International compatibility matrix

| Concern | Target standard | Current status | Next action |
|---|---|---|---|
| Locale | BCP-47 (`vi-VN`, `en-US`) | partial | normalize locale config and translations |
| Country | ISO-3166 (`VN`, `US`, `TH`) | gap | add country reference and player/venue country |
| Administrative area | country-specific hierarchy | partial text region | add normalized location adapter |
| Timezone | IANA (`Asia/Ho_Chi_Minh`) | mostly fixed default | venue timezone + UTC storage audit |
| Currency | ISO-4217 (`VND`, `USD`) | VND assumptions remain | money value object/column policy |
| Rating | provider interface + internal/external separation | foundation exists | consented connector and sync status |
| Skill | configurable 2.0–5.5+ bands | foundation exists | localized labels and policy version |
| Rules | versioned competition rulesets | gap | ruleset tables + event snapshot |
| Ranking | authority/policy/season/discipline | partial | country and rolling-season dimensions |
| Privacy | public/member/private/restricted | partial | central data policy service |
| API | public/private/partner versioning | foundation exists | OpenAPI YAML CI validation |
| Scalability | queue isolation/cache/search abstraction | partial | typed jobs, metrics, provider interfaces |

“International-ready” here means architecture compatibility. It does not claim an internationally authorized ranking or external-provider certification.
