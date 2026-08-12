# Rating Algorithm V1

## Why a separate scale

Legacy ELO values around 1000 remain available for old UI/API compatibility. Canonical platform rating uses a 2.0–5.5+ skill scale with three decimal precision, stored in `player_rating_profiles.rating_value`.

## Match performance

For each game, points won and points lost are used to calculate margin. The match result is the mean of game performances, with the winner outcome used as the minimum signal when score data is absent. Walkovers and disqualifications follow policy and may be ineligible.

## Explanation

Every transaction stores before/after, expected/actual performance, match weight, reason, policy version and source metadata so the UI can say why a rating moved without exposing external-provider internals.
