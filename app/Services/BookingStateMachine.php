<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Booking lifecycle rules shared by web and API use cases.
 *
 * A booking status must never be changed by assigning an arbitrary string in
 * a controller. Keeping the graph in one place makes invalid transitions
 * fail fast and keeps historical booking data consistent.
 */
class BookingStateMachine
{
    private const TRANSITIONS = [
        'draft'      => ['hold', 'pending', 'cancelled'],
        'hold'       => ['reserved', 'paid', 'cancelled', 'expired'],
        'pending'    => ['reserved', 'paid', 'cancelled', 'expired'],
        'reserved'   => ['paid', 'checked_in', 'cancelled', 'refunded', 'no_show'],
        'paid'       => ['checked_in', 'cancelled', 'refunded', 'no_show'],
        'checked_in' => ['in_progress', 'completed'],
        'in_progress'=> ['completed'],
        'cancelled'  => ['refunded'],
        'no_show'    => ['refunded'],
        'completed'  => [],
        'refunded'   => [],
        'expired'    => [],
    ];

    public function canTransition(?string $from, string $to): bool
    {
        if ($from === null || $from === $to) {
            return $from === $to;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function assertTransition(?string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                sprintf('Booking transition %s -> %s is not allowed.', $from ?? 'null', $to)
            );
        }
    }

    public function transitionsFrom(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }
}
