# Identity architecture

```text
User (authentication)
  └── organization memberships (future normalized membership)
Player operational projection (tenant-scoped)
  └── Player Competitive Profile / Passport (platform identity)
       ├── claims
       ├── club memberships
       ├── merge history
       └── public privacy policy
```

## Identity resolution

1. National Player ID: exact match.
2. Provider + external player ID: exact match after consent.
3. Verified phone/email: possible exact match, never silent merge.
4. Name/location: suggestion only.

Unclaimed profiles can be created by a club or organizer. Login → claim → verify → link user. A merge marks the old profile as merged, redirects references to the canonical player and preserves every historical ID/audit record.

## Privacy

Public profile may contain display name, public ID, avatar, public rating/ranking and verified status. It must not contain phone, email, birthday, payment, booking history or private club notes.
