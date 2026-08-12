<?php

namespace App\Services;

class RatingEligibilityService
{
    public function validate(array $context): array
    {
        $match = (object) ($context['match'] ?? []);
        $result = (object) ($context['result'] ?? []);
        $participants = $context['participants'] ?? [];
        $reasons = [];
        if (($match->status ?? null) !== 'official') $reasons[] = 'MATCH_NOT_OFFICIAL';
        if (($match->verification_status ?? null) !== 'official') $reasons[] = 'MATCH_NOT_VERIFIED';
        if (($result->status ?? null) !== 'official') $reasons[] = 'RESULT_NOT_OFFICIAL';
        if (in_array(($match->status ?? null), ['disputed', 'cancelled'], true) || in_array(($result->status ?? null), ['disputed', 'cancelled'], true)) $reasons[] = 'RESULT_DISPUTED_OR_VOID';
        if (count($participants) < 2) $reasons[] = 'NOT_ENOUGH_PARTICIPANTS';
        $sides = array_unique(array_map(static fn ($p): int => (int) ($p->side ?? $p['side'] ?? 0), $participants));
        if (count($sides) < 2) $reasons[] = 'INVALID_SIDES';
        if (! in_array((string) ($context['discipline'] ?? 'singles'), ['singles', 'doubles', 'mixed_doubles'], true)) $reasons[] = 'DISCIPLINE_UNSUPPORTED';
        if (isset($context['games']) && ! is_array($context['games'])) $reasons[] = 'INVALID_SCORE_PAYLOAD';
        return ['eligible' => $reasons === [], 'code' => $reasons[0] ?? 'ELIGIBLE', 'reasons' => $reasons];
    }
}
