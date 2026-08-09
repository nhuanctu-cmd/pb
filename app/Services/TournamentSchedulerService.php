<?php

namespace App\Services;

use App\Models\TournamentBracketModel;
use App\Models\TournamentGroupModel;
use App\Models\TournamentGroupTeamModel;
use App\Models\TournamentMatchModel;
use App\Models\TournamentScheduleLockModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class TournamentSchedulerService
{
    private BaseConnection $db;
    private TournamentGroupModel $groupModel;
    private TournamentGroupTeamModel $groupTeamModel;
    private TournamentMatchModel $matchModel;
    private TournamentBracketModel $bracketModel;
    private TournamentScheduleLockModel $lockModel;

    private int $matchMinutes = 60;
    private int $restMinutes = 30;
    private string $dayStart = '08:00:00';
    private string $dayEnd = '22:00:00';

    public function __construct()
    {
        $this->db = Database::connect();
        $this->groupModel = new TournamentGroupModel();
        $this->groupTeamModel = new TournamentGroupTeamModel();
        $this->matchModel = new TournamentMatchModel();
        $this->bracketModel = new TournamentBracketModel();
        $this->lockModel = new TournamentScheduleLockModel();
    }

    public function generateGroups($categoryId, $numberOfGroups): array
    {
        $categoryId = (int) $categoryId;
        $numberOfGroups = max(1, (int) $numberOfGroups);
        $context = $this->getCategoryContext($categoryId);

        $this->db->transStart();
        $oldGroupIds = array_column($this->getGroups($categoryId), 'id');
        if ($oldGroupIds) {
            $this->groupTeamModel->whereIn('group_id', $oldGroupIds)->delete();
        }
        $this->groupModel->where('category_id', $categoryId)->delete();

        $groups = [];
        for ($i = 1; $i <= $numberOfGroups; $i++) {
            $id = $this->groupModel->insert([
                'tenant_id' => $context['tenant_id'],
                'tournament_id' => $context['tournament_id'],
                'category_id' => $categoryId,
                'group_name' => 'Bảng ' . $this->groupLabel($i),
                'sort_order' => $i,
            ]);
            $groups[] = $this->groupModel->find($id);
        }
        $this->db->transComplete();

        return $groups;
    }

    public function seedTeams($categoryId): array
    {
        $categoryId = (int) $categoryId;
        $groups = $this->getGroups($categoryId);
        $teams = $this->getRegisteredTeams($categoryId);

        if (! $groups || ! $teams) {
            return [];
        }

        usort($teams, static function (array $a, array $b): int {
            return [$a['seed_no'] ?? PHP_INT_MAX, -($a['rating'] ?? 0), $a['team_id']]
                <=> [$b['seed_no'] ?? PHP_INT_MAX, -($b['rating'] ?? 0), $b['team_id']];
        });

        $buckets = array_fill(0, count($groups), []);
        foreach ($teams as $team) {
            $bestIndex = $this->bestGroupIndex($buckets, $team);
            $buckets[$bestIndex][] = $team;
        }

        $this->db->transStart();
        $this->groupTeamModel->whereIn('group_id', array_column($groups, 'id'))->delete();
        foreach ($groups as $index => $group) {
            foreach ($buckets[$index] as $team) {
                $this->groupTeamModel->insert([
                    'tenant_id' => (int) $group->tenant_id,
                    'group_id' => (int) $group->id,
                    'team_id' => (int) $team['team_id'],
                    'seed_no' => $team['seed_no'],
                ]);
            }
        }
        $this->db->transComplete();

        return $this->getGroupsWithTeams($categoryId);
    }

    public function generateRoundRobinMatches($groupId): array
    {
        $groupId = (int) $groupId;
        $group = $this->groupModel->find($groupId);
        if (! $group) {
            return [];
        }

        $teams = $this->getGroupTeams($groupId);
        $teamIds = array_map(static fn ($team) => (int) $team->team_id, $teams);
        if (count($teamIds) < 2) {
            return [];
        }

        if (count($teamIds) % 2 === 1) {
            $teamIds[] = null;
        }

        $rounds = $this->roundRobinPairs($teamIds);
        $this->matchModel
            ->where('group_id', $groupId)
            ->where('is_locked', 0)
            ->delete();

        $created = [];
        $matchNo = $this->nextMatchNo((int) $group->category_id);
        foreach ($rounds as $roundIndex => $pairs) {
            foreach ($pairs as $pair) {
                [$teamA, $teamB] = $pair;
                $winner = $teamA && ! $teamB ? $teamA : ($teamB && ! $teamA ? $teamB : null);
                $id = $this->matchModel->insert([
                    'tenant_id' => (int) $group->tenant_id,
                    'tournament_id' => (int) $group->tournament_id,
                    'category_id' => (int) $group->category_id,
                    'group_id' => $groupId,
                    'round_name' => 'Vòng bảng ' . ($roundIndex + 1),
                    'match_no' => $matchNo++,
                    'team_a_id' => $teamA,
                    'team_b_id' => $teamB,
                    'winner_team_id' => $winner,
                    'status' => $winner ? 'completed' : 'scheduled',
                    'is_locked' => 0,
                ]);
                $created[] = $this->matchModel->find($id);
            }
        }

        return $created;
    }

    public function generateKnockoutBracket($categoryId): array
    {
        $categoryId = (int) $categoryId;
        $context = $this->getCategoryContext($categoryId);
        $teams = $this->getRegisteredTeams($categoryId);
        if (count($teams) < 2) {
            return [];
        }

        usort($teams, static fn (array $a, array $b): int => ($a['seed_no'] ?? PHP_INT_MAX) <=> ($b['seed_no'] ?? PHP_INT_MAX));
        $slots = $this->seededBracketSlots(array_column($teams, 'team_id'));

        $this->matchModel->where('category_id', $categoryId)->where('group_id', null)->where('is_locked', 0)->delete();
        $this->bracketModel->where('category_id', $categoryId)->delete();

        $roundMatches = [];
        $matchNo = $this->nextMatchNo($categoryId);
        $roundNo = 1;
        $pairs = array_chunk($slots, 2);
        foreach ($pairs as $position => $pair) {
            [$teamA, $teamB] = [$pair[0] ?? null, $pair[1] ?? null];
            $winner = $teamA && ! $teamB ? $teamA : ($teamB && ! $teamA ? $teamB : null);
            $roundMatches[] = $this->createBracketMatch($context, $categoryId, $roundNo, $position + 1, $matchNo++, $teamA, $teamB, $winner);
        }

        $allRounds = [$roundMatches];
        while (count($roundMatches) > 1) {
            $previousRound = $roundMatches;
            $roundMatches = [];
            $roundNo++;
            for ($i = 0; $i < count($previousRound); $i += 2) {
                $roundMatches[] = $this->createBracketMatch($context, $categoryId, $roundNo, (int) floor($i / 2) + 1, $matchNo++);
            }
            foreach ($previousRound as $index => $child) {
                $next = $roundMatches[(int) floor($index / 2)] ?? null;
                if ($next) {
                    $this->db->table('tournament_brackets')->where('match_id', $child->id)->update(['next_match_id' => $next->id]);
                    $this->db->table('tournament_brackets')->where('match_id', $next->id)->update(['parent_match_id' => $child->id]);
                }
            }
            $allRounds[] = $roundMatches;
        }

        return array_merge(...$allRounds);
    }

    public function assignCourts($categoryId): array
    {
        $categoryId = (int) $categoryId;
        $context = $this->getCategoryContext($categoryId);
        $courts = $this->getAvailableCourtIds((int) $context['tenant_id']);
        if (! $courts) {
            return [];
        }

        $matches = $this->matchModel
            ->where('category_id', $categoryId)
            ->where('status !=', 'cancelled')
            ->where('is_locked', 0)
            ->orderBy('match_no', 'ASC')
            ->findAll();

        $updated = [];
        foreach ($matches as $index => $match) {
            if (! $match->court_id) {
                $courtId = $courts[$index % count($courts)];
                $this->matchModel->update($match->id, ['court_id' => $courtId]);
                $match->court_id = $courtId;
            }
            $updated[] = $match;
        }

        return $updated;
    }

    public function assignTimeSlots($categoryId): array
    {
        $categoryId = (int) $categoryId;
        $context = $this->getCategoryContext($categoryId);
        $courts = $this->getAvailableCourtIds((int) $context['tenant_id']);
        if (! $courts) {
            return [];
        }

        $date = $context['start_date'] ?? date('Y-m-d');
        $matches = $this->matchModel
            ->where('category_id', $categoryId)
            ->where('status', 'scheduled')
            ->where('is_locked', 0)
            ->orderBy('match_no', 'ASC')
            ->findAll();

        $assigned = [];
        $schedule = $this->buildScheduleState($categoryId);
        foreach ($matches as $match) {
            $slot = $this->findSlot($match, $courts, $date, $schedule);
            $this->matchModel->update($match->id, [
                'court_id' => $slot['court_id'],
                'scheduled_date' => $slot['date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
            ]);

            $match->court_id = $slot['court_id'];
            $match->scheduled_date = $slot['date'];
            $match->start_time = $slot['start_time'];
            $match->end_time = $slot['end_time'];
            $assigned[] = $match;
            $this->addToScheduleState($schedule, $match);
        }

        return $assigned;
    }

    public function detectConflicts($categoryId): array
    {
        $matches = $this->matchModel
            ->where('category_id', (int) $categoryId)
            ->where('status !=', 'cancelled')
            ->orderBy('scheduled_date', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();

        $conflicts = [];
        foreach ($matches as $match) {
            if ($match->status === 'scheduled' && (! $match->court_id || ! $match->scheduled_date || ! $match->start_time || ! $match->end_time)) {
                $conflicts[] = $this->conflict('missing_slot', $match, 'Trận chưa có đủ sân hoặc khung giờ.');
            }
        }

        for ($i = 0; $i < count($matches); $i++) {
            for ($j = $i + 1; $j < count($matches); $j++) {
                $a = $matches[$i];
                $b = $matches[$j];
                if (! $this->sameDateWithTimes($a, $b)) {
                    continue;
                }
                if ((int) $a->court_id > 0 && (int) $a->court_id === (int) $b->court_id && $this->overlaps($a, $b)) {
                    $conflicts[] = $this->conflict('court_overlap', $a, 'Sân bị trùng lịch.', $b);
                }
                if ($this->shareTeam($a, $b)) {
                    if ($this->overlaps($a, $b)) {
                        $conflicts[] = $this->conflict('team_overlap', $a, 'Đội bị xếp trùng giờ.', $b);
                    } elseif ($this->minutesBetween($a->end_time, $b->start_time) < $this->restMinutes && $this->minutesBetween($a->end_time, $b->start_time) >= 0) {
                        $conflicts[] = $this->conflict('team_rest', $a, 'Đội không đủ thời gian nghỉ tối thiểu.', $b);
                    }
                }
            }
        }

        return $conflicts;
    }

    public function rerunUnlockedSchedule($categoryId): array
    {
        $this->db->table('tournament_matches')
            ->where('category_id', (int) $categoryId)
            ->where('is_locked', 0)
            ->update(['court_id' => null, 'scheduled_date' => null, 'start_time' => null, 'end_time' => null]);

        $this->assignCourts($categoryId);
        $this->assignTimeSlots($categoryId);

        return [
            'matches' => $this->getMatches((int) $categoryId),
            'conflicts' => $this->detectConflicts((int) $categoryId),
        ];
    }

    public function lockMatch($matchId): bool
    {
        $match = $this->matchModel->find((int) $matchId);
        if (! $match) {
            return false;
        }

        $this->matchModel->update($match->id, ['is_locked' => 1]);
        $exists = $this->lockModel->where('lock_type', 'match')->where('ref_id', $match->id)->first();
        if (! $exists) {
            $this->lockModel->insert([
                'tenant_id' => (int) $match->tenant_id,
                'tournament_id' => (int) $match->tournament_id,
                'lock_type' => 'match',
                'ref_id' => (int) $match->id,
                'reason' => 'Admin locked match',
            ]);
        }

        return true;
    }

    public function unlockMatch($matchId): bool
    {
        $match = $this->matchModel->find((int) $matchId);
        if (! $match) {
            return false;
        }

        $this->matchModel->update($match->id, ['is_locked' => 0]);
        $this->lockModel->where('lock_type', 'match')->where('ref_id', $match->id)->delete();

        return true;
    }

    public function moveMatch($matchId, $courtId, $date, $startTime): ?object
    {
        $match = $this->matchModel->find((int) $matchId);
        if (! $match) {
            return null;
        }

        $startTime = $this->normalizeTime((string) $startTime);
        $endTime = $this->addMinutes($startTime, $this->matchMinutes);
        $this->matchModel->update($match->id, [
            'court_id' => (int) $courtId,
            'scheduled_date' => (string) $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return $this->matchModel->find($match->id);
    }

    public function moveTeamToGroup(int $teamId, int $groupId): bool
    {
        $group = $this->groupModel->find($groupId);
        if (! $group) {
            return false;
        }

        $row = $this->groupTeamModel
            ->select('tournament_group_teams.*')
            ->join('tournament_groups', 'tournament_groups.id = tournament_group_teams.group_id')
            ->where('tournament_groups.category_id', $group->category_id)
            ->where('tournament_group_teams.team_id', $teamId)
            ->first();

        if ($row) {
            return $this->groupTeamModel->update($row->id, ['group_id' => $groupId]);
        }

        return (bool) $this->groupTeamModel->insert([
            'tenant_id' => (int) $group->tenant_id,
            'group_id' => $groupId,
            'team_id' => $teamId,
        ]);
    }

    public function getGroupsWithTeams(int $categoryId): array
    {
        $groups = $this->getGroups($categoryId);
        foreach ($groups as $group) {
            $group->teams = $this->getGroupTeams((int) $group->id);
        }

        return $groups;
    }

    public function getMatches(int $categoryId): array
    {
        return $this->matchModel
            ->where('category_id', $categoryId)
            ->orderBy('scheduled_date', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->orderBy('match_no', 'ASC')
            ->findAll();
    }

    private function getGroups(int $categoryId): array
    {
        return $this->groupModel
            ->where('category_id', $categoryId)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    private function getGroupTeams(int $groupId): array
    {
        $builder = $this->groupTeamModel->select('tournament_group_teams.*');
        if ($this->db->tableExists('teams')) {
            $fields = $this->db->getFieldNames('teams');
            $nameField = in_array('name', $fields, true) ? 'name' : (in_array('team_name', $fields, true) ? 'team_name' : null);
            if ($nameField) {
                $builder->select('teams.' . $nameField . ' as team_name');
            }
            if (in_array('club_id', $fields, true)) {
                $builder->select('teams.club_id');
            }
            $builder->join('teams', 'teams.id = tournament_group_teams.team_id', 'left');
        }

        return $builder->where('group_id', $groupId)->orderBy('seed_no', 'ASC')->findAll();
    }

    private function getCategoryContext(int $categoryId): array
    {
        $context = [
            'tenant_id' => (int) ((function_exists('current_tenant_id') ? current_tenant_id() : null) ?? session('tenant_id') ?? 1),
            'tournament_id' => 0,
            'start_date' => date('Y-m-d'),
        ];

        if ($this->db->tableExists('tournament_categories')) {
            $row = $this->db->table('tournament_categories')->where('id', $categoryId)->get()->getRowArray();
            if ($row) {
                return [
                    'tenant_id' => (int) ($row['tenant_id'] ?? $context['tenant_id']),
                    'tournament_id' => (int) ($row['tournament_id'] ?? 0),
                    'start_date' => $row['start_date'] ?? $context['start_date'],
                ];
            }
        }

        if ($this->db->tableExists('tournament_teams')) {
            $row = $this->db->table('tournament_teams')->where('category_id', $categoryId)->get(1)->getRowArray();
            if ($row) {
                $context['tenant_id'] = (int) ($row['tenant_id'] ?? $context['tenant_id']);
                $context['tournament_id'] = (int) ($row['tournament_id'] ?? 0);
            }
        }

        return $context;
    }

    private function getRegisteredTeams(int $categoryId): array
    {
        if ($this->db->tableExists('tournament_teams')) {
            $fields = $this->db->getFieldNames('tournament_teams');
            $teamColumn = in_array('team_id', $fields, true) ? 'team_id' : 'id';
            $builder = $this->db->table('tournament_teams')->where('category_id', $categoryId);
            $rows = $builder->get()->getResultArray();

            return array_map(function (array $row) use ($teamColumn): array {
                return [
                    'team_id' => (int) $row[$teamColumn],
                    'seed_no' => isset($row['seed_no']) ? (int) $row['seed_no'] : null,
                    'club_id' => isset($row['club_id']) ? (int) $row['club_id'] : null,
                    'rating' => isset($row['rating']) ? (float) $row['rating'] : 0,
                ];
            }, $rows);
        }

        if ($this->db->tableExists('teams') && in_array('category_id', $this->db->getFieldNames('teams'), true)) {
            $rows = $this->db->table('teams')->where('category_id', $categoryId)->get()->getResultArray();
            return array_map(static function (array $row): array {
                return [
                    'team_id' => (int) $row['id'],
                    'seed_no' => isset($row['seed_no']) ? (int) $row['seed_no'] : null,
                    'club_id' => isset($row['club_id']) ? (int) $row['club_id'] : null,
                    'rating' => isset($row['rating']) ? (float) $row['rating'] : 0,
                ];
            }, $rows);
        }

        $existing = $this->db->table('tournament_group_teams')
            ->select('tournament_group_teams.team_id, tournament_group_teams.seed_no')
            ->join('tournament_groups', 'tournament_groups.id = tournament_group_teams.group_id')
            ->where('tournament_groups.category_id', $categoryId)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'team_id' => (int) $row['team_id'],
            'seed_no' => isset($row['seed_no']) ? (int) $row['seed_no'] : null,
            'club_id' => null,
            'rating' => 0,
        ], $existing);
    }

    private function bestGroupIndex(array $buckets, array $team): int
    {
        $bestIndex = 0;
        $bestScore = PHP_INT_MAX;
        foreach ($buckets as $index => $bucket) {
            $sameClub = 0;
            foreach ($bucket as $existing) {
                if ($team['club_id'] && $existing['club_id'] && (int) $team['club_id'] === (int) $existing['club_id']) {
                    $sameClub++;
                }
            }
            $score = count($bucket) * 100 + $sameClub * 25 + $index;
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    private function roundRobinPairs(array $teamIds): array
    {
        $rounds = [];
        $count = count($teamIds);
        for ($round = 0; $round < $count - 1; $round++) {
            $pairs = [];
            for ($i = 0; $i < $count / 2; $i++) {
                $pairs[] = [$teamIds[$i], $teamIds[$count - 1 - $i]];
            }
            $rounds[] = $pairs;
            $fixed = array_shift($teamIds);
            $last = array_pop($teamIds);
            array_unshift($teamIds, $fixed);
            array_splice($teamIds, 1, 0, [$last]);
        }

        return $rounds;
    }

    private function seededBracketSlots(array $teamIds): array
    {
        $size = 1;
        while ($size < count($teamIds)) {
            $size *= 2;
        }

        $slots = array_fill(0, $size, null);
        $positions = $this->seedOrder($size);

        foreach ($teamIds as $seedIndex => $teamId) {
            $slotIndex = ($positions[$seedIndex] ?? ($seedIndex + 1)) - 1;
            $slots[$slotIndex] = (int) $teamId;
        }

        return $slots;
    }

    private function seedOrder(int $size): array
    {
        $order = [1];
        while (count($order) < $size) {
            $nextSize = count($order) * 2;
            $next = [];
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $nextSize + 1 - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    private function createBracketMatch(array $context, int $categoryId, int $roundNo, int $position, int $matchNo, ?int $teamA = null, ?int $teamB = null, ?int $winner = null): object
    {
        $roundName = $roundNo === 1 ? 'Knockout R1' : 'Knockout R' . $roundNo;
        $id = $this->matchModel->insert([
            'tenant_id' => $context['tenant_id'],
            'tournament_id' => $context['tournament_id'],
            'category_id' => $categoryId,
            'group_id' => null,
            'round_name' => $roundName,
            'match_no' => $matchNo,
            'team_a_id' => $teamA,
            'team_b_id' => $teamB,
            'winner_team_id' => $winner,
            'status' => $winner ? 'completed' : 'scheduled',
            'is_locked' => 0,
        ]);

        $this->bracketModel->insert([
            'tenant_id' => $context['tenant_id'],
            'tournament_id' => $context['tournament_id'],
            'category_id' => $categoryId,
            'match_id' => $id,
            'bracket_position' => 'R' . $roundNo . '-' . $position,
            'round_no' => $roundNo,
        ]);

        return $this->matchModel->find($id);
    }

    private function nextMatchNo(int $categoryId): int
    {
        $max = $this->matchModel->where('category_id', $categoryId)->selectMax('match_no')->first();
        return (int) ($max->match_no ?? 0) + 1;
    }

    private function getAvailableCourtIds(int $tenantId): array
    {
        if (! $this->db->tableExists('courts')) {
            return [];
        }

        return array_map('intval', array_column(
            $this->db->table('courts')
                ->select('id')
                ->where('tenant_id', $tenantId)
                ->where('status !=', 'inactive')
                ->orderBy('sort_order', 'ASC')
                ->orderBy('code', 'ASC')
                ->get()
                ->getResultArray(),
            'id'
        ));
    }

    private function buildScheduleState(int $categoryId): array
    {
        $state = ['court' => [], 'team' => []];
        $locked = $this->matchModel
            ->where('category_id', $categoryId)
            ->where('is_locked', 1)
            ->where('scheduled_date IS NOT NULL', null, false)
            ->findAll();

        foreach ($locked as $match) {
            $this->addToScheduleState($state, $match);
        }

        return $state;
    }

    private function addToScheduleState(array &$state, object $match): void
    {
        if (! $match->scheduled_date || ! $match->start_time || ! $match->end_time) {
            return;
        }

        if ($match->court_id) {
            $state['court'][$match->court_id][$match->scheduled_date][] = [$match->start_time, $match->end_time, $match->id];
        }
        foreach ([$match->team_a_id, $match->team_b_id] as $teamId) {
            if ($teamId) {
                $state['team'][$teamId][$match->scheduled_date][] = [$match->start_time, $match->end_time, $match->id];
            }
        }
    }

    private function findSlot(object $match, array $courts, string $date, array $schedule): array
    {
        $cursorDate = $date;
        for ($day = 0; $day < 14; $day++) {
            for ($minutes = $this->toMinutes($this->dayStart); $minutes + $this->matchMinutes <= $this->toMinutes($this->dayEnd); $minutes += $this->matchMinutes) {
                $start = $this->minutesToTime($minutes);
                $end = $this->minutesToTime($minutes + $this->matchMinutes);
                foreach ($courts as $courtId) {
                    if ($this->slotIsFree($match, $courtId, $cursorDate, $start, $end, $schedule)) {
                        return ['court_id' => $courtId, 'date' => $cursorDate, 'start_time' => $start, 'end_time' => $end];
                    }
                }
            }
            $cursorDate = date('Y-m-d', strtotime($cursorDate . ' +1 day'));
        }

        return ['court_id' => $courts[0], 'date' => $date, 'start_time' => $this->dayStart, 'end_time' => $this->addMinutes($this->dayStart, $this->matchMinutes)];
    }

    private function slotIsFree(object $match, int $courtId, string $date, string $start, string $end, array $schedule): bool
    {
        foreach ($schedule['court'][$courtId][$date] ?? [] as $slot) {
            if ($this->timeOverlaps($start, $end, $slot[0], $slot[1])) {
                return false;
            }
        }

        foreach ([$match->team_a_id, $match->team_b_id] as $teamId) {
            if (! $teamId) {
                continue;
            }
            foreach ($schedule['team'][$teamId][$date] ?? [] as $slot) {
                if ($this->timeOverlaps($start, $end, $slot[0], $slot[1])) {
                    return false;
                }
                $gapBefore = $this->minutesBetween($slot[1], $start);
                $gapAfter = $this->minutesBetween($end, $slot[0]);
                if (($gapBefore >= 0 && $gapBefore < $this->restMinutes) || ($gapAfter >= 0 && $gapAfter < $this->restMinutes)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function conflict(string $type, object $match, string $message, ?object $other = null): array
    {
        return [
            'type' => $type,
            'match_id' => (int) $match->id,
            'other_match_id' => $other ? (int) $other->id : null,
            'message' => $message,
        ];
    }

    private function sameDateWithTimes(object $a, object $b): bool
    {
        return $a->scheduled_date && $b->scheduled_date
            && $a->scheduled_date === $b->scheduled_date
            && $a->start_time && $a->end_time && $b->start_time && $b->end_time;
    }

    private function overlaps(object $a, object $b): bool
    {
        return $this->timeOverlaps($a->start_time, $a->end_time, $b->start_time, $b->end_time);
    }

    private function shareTeam(object $a, object $b): bool
    {
        $aTeams = array_filter([(int) $a->team_a_id, (int) $a->team_b_id]);
        $bTeams = array_filter([(int) $b->team_a_id, (int) $b->team_b_id]);
        return (bool) array_intersect($aTeams, $bTeams);
    }

    private function timeOverlaps(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $this->toMinutes($aStart) < $this->toMinutes($bEnd)
            && $this->toMinutes($aEnd) > $this->toMinutes($bStart);
    }

    private function minutesBetween(string $from, string $to): int
    {
        return $this->toMinutes($to) - $this->toMinutes($from);
    }

    private function toMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        return $hour * 60 + $minute;
    }

    private function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', (int) floor($minutes / 60), $minutes % 60);
    }

    private function addMinutes(string $time, int $minutes): string
    {
        return $this->minutesToTime($this->toMinutes($time) + $minutes);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function groupLabel(int $index): string
    {
        $label = '';
        while ($index > 0) {
            $index--;
            $label = chr(65 + ($index % 26)) . $label;
            $index = (int) floor($index / 26);
        }

        return $label;
    }
}
