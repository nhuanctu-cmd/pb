<?php

namespace App\Controllers\Api;

use App\Services\TournamentSchedulerService;
use CodeIgniter\RESTful\ResourceController;

class TournamentSchedulerApi extends ResourceController
{
    protected $format = 'json';
    private TournamentSchedulerService $scheduler;

    public function __construct()
    {
        $this->scheduler = new TournamentSchedulerService();
    }

    public function autoSchedule($tournamentId = null)
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $categoryId = (int) ($payload['category_id'] ?? 0);
        $groups = (int) ($payload['groups'] ?? 2);

        if (! $categoryId) {
            return $this->failValidationErrors(['category_id' => 'category_id is required']);
        }

        $createdGroups = $this->scheduler->generateGroups($categoryId, $groups);
        $this->scheduler->seedTeams($categoryId);
        foreach ($createdGroups as $group) {
            $this->scheduler->generateRoundRobinMatches((int) $group->id);
        }
        $this->scheduler->generateKnockoutBracket($categoryId);
        $this->scheduler->assignCourts($categoryId);
        $this->scheduler->assignTimeSlots($categoryId);

        return $this->respond([
            'success' => true,
            'data' => [
                'groups' => $this->scheduler->getGroupsWithTeams($categoryId),
                'matches' => $this->scheduler->getMatches($categoryId),
                'conflicts' => $this->scheduler->detectConflicts($categoryId),
            ],
        ]);
    }

    public function moveMatch($id = null)
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        foreach (['court_id', 'date', 'start_time'] as $field) {
            if (empty($payload[$field])) {
                return $this->failValidationErrors([$field => $field . ' is required']);
            }
        }

        $match = $this->scheduler->moveMatch((int) $id, (int) $payload['court_id'], $payload['date'], $payload['start_time']);
        if (! $match) {
            return $this->failNotFound('Match not found');
        }

        return $this->respond(['success' => true, 'data' => $match]);
    }

    public function lockMatch($id = null)
    {
        $locked = $this->scheduler->lockMatch((int) $id);
        if (! $locked) {
            return $this->failNotFound('Match not found');
        }

        return $this->respond(['success' => true]);
    }

    public function unlockMatch($id = null)
    {
        $unlocked = $this->scheduler->unlockMatch((int) $id);
        if (! $unlocked) {
            return $this->failNotFound('Match not found');
        }

        return $this->respond(['success' => true]);
    }

    public function conflicts($tournamentId = null)
    {
        $categoryId = (int) $this->request->getGet('category_id');
        if (! $categoryId) {
            return $this->failValidationErrors(['category_id' => 'category_id is required']);
        }

        return $this->respond([
            'success' => true,
            'data' => $this->scheduler->detectConflicts($categoryId),
        ]);
    }
}
