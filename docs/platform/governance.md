# Governance architecture

```text
Organizer submits event
  → Ranking Authority reviews sanction
  → Event is COMMUNITY / VERIFIED / SANCTIONED / OFFICIAL
  → Result authority confirms official result
  → Rating/ranking consumers process immutable version
```

Governance entities: ranking authority, sanction, official result authority, dispute, appeal, disciplinary status and integrity case. Decisions require actor, reason, evidence, timestamp and audit record.

Heuristics can open a review case for duplicate identity, suspicious match, sandbagging or impossible score. They must never auto-convict or auto-ban. A correction appends a result version and creates compensation/rebuild work for derived rating/ranking ledgers.
