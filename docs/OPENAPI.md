# Pickleball System API v1

Base URL: `/api/v1`. Authenticated endpoints require `Authorization: Bearer <token>` or a tenant-scoped `X-API-Key`. Requests are rate-limited to 120 requests per minute per client identity and return HTTP 429 with `Retry-After` when exceeded.

## Core mobile contract

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/auth/login` | No | Login and receive signed access token |
| POST | `/auth/refresh` | Yes | Rotate/refresh access token |
| GET | `/booking/available-slots` | No | Tenant-scoped court availability |
| GET | `/bookings` | Yes | Tenant booking list |
| POST | `/bookings` | Yes | Create booking through `BookingService` |
| POST | `/bookings/{id}/cancel` | Yes | Cancel booking with tenant boundary |
| GET | `/player/profile` | Yes | Current player profile and membership |
| GET | `/player/wallet` | Yes | Wallet balance and ledger |
| GET | `/player/ranking` | Yes | Tenant ranking |
| GET | `/coaching/sessions?date=YYYY-MM-DD` | Yes | Open/full coaching sessions and current player entry |
| POST | `/coaching/sessions/{id}/join` | Yes | Join or enter coaching waitlist |
| POST | `/coaching/entries/{id}/pay` | Yes | Pay coaching invoice from player wallet |
| GET | `/competitions` | Yes | Tenant competition list |
| GET | `/competitions/{id}` | Yes | Event, participants, standings, fixtures and ladder challenges |
| POST | `/competitions/ladder/{id}/respond` | Yes | Accept/reject challenge as current player |
| POST | `/competitions/participants/{id}/pay` | Yes | Pay competition entry invoice from player wallet |
| GET | `/community/posts` | Yes | Tenant-scoped community feed |
| POST | `/community/posts` | Yes | Create a community post |
| POST | `/community/posts/{id}/comments` | Yes | Add a comment to a published post |
| POST | `/community/posts/{id}/reactions` | Yes | Create or update a post reaction |

## Response envelope

Successful responses use `{ "status": 200, "message": "...", "data": ... }`. Validation failures use HTTP 422 and an `errors` object; missing tenant/entity uses 404 or 403; authentication failures use 401. All writes use the existing service transaction, tenant validation, audit logging and idempotency rules where money is involved.

## Contract verification

Run `php spark routes` to verify route registration and `php vendor/bin/phpunit` for the API/auth and domain regression suite. The current development baseline is 124 tests and 342 assertions.
