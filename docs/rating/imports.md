# Rating import workflow

Imports are staging data, never a direct write to `player_rating_profiles` or `rating_transactions`.

The protected API and `RatingImportService` enforce this sequence:

`UPLOAD → PREVIEW → IDENTITY MATCHING → DUPLICATE CHECK / SCORE VALIDATION → SOURCE VERIFICATION → IMPORT`

Rows are matched only inside the tenant using player ID, identity claims, phone/email, and exact name as progressively weaker signals. Ambiguous or unmatched rows cannot pass validation. Rating values are bounded to 2.000–6.000.

The final import creates `player_skill_claims` with source `club`, `coach`, or `external_provider`. It does not create an official rating transaction. A verified claim can be considered by `InitialRatingService` when a player first enters the official engine; subsequent official ratings come only from official result versions.

Endpoints:

- `POST /api/v1/rating/imports`
- `POST /api/v1/rating/imports/{job}/preview`
- `POST /api/v1/rating/imports/{job}/matching`
- `POST /api/v1/rating/imports/{job}/validate`
- `POST /api/v1/rating/imports/{job}/verify`
- `POST /api/v1/rating/imports/{job}/import`

These endpoints require the existing API authentication and tenant context. A club can submit evidence and claims; it cannot set official rating directly.
