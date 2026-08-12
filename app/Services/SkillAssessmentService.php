<?php

namespace App\Services;

use Config\Database;

class SkillAssessmentService
{
    private const DEFAULT_WEIGHTS = ['serve' => 1, 'return' => 1, 'forehand' => 1, 'backhand' => 1, 'dink' => 1, 'third_shot' => 1, 'volley' => 1, 'reset' => 1, 'transition' => 1, 'positioning' => 1, 'strategy' => 1, 'consistency' => 1, 'match_experience' => 1];
    protected $db;

    public function __construct() { $this->db = Database::connect(); }

    public function assess(int $tenantId, int $playerId, string $discipline, array $answers, array $weights = []): array
    {
        if (! $this->db->tableExists('player_skill_assessments')) return ['success' => false, 'message' => 'Assessment foundation migration chưa được chạy.'];
        $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->get()->getRow();
        if (! $disciplineRow) return ['success' => false, 'message' => 'Discipline không hợp lệ.'];
        $weights = array_merge(self::DEFAULT_WEIGHTS, $weights);
        $weighted = $weightTotal = 0.0;
        $cleanAnswers = [];
        foreach ($weights as $skill => $weight) {
            if (! array_key_exists($skill, $answers) || $answers[$skill] === '') continue;
            $score = (float) $answers[$skill];
            if ($score < 0 || $score > 5) return ['success' => false, 'message' => 'Mỗi tiêu chí phải trong khoảng 0–5.'];
            $cleanAnswers[$skill] = $score;
            $weighted += $score * (float) $weight;
            $weightTotal += (float) $weight;
        }
        if (! $cleanAnswers || $weightTotal <= 0) return ['success' => false, 'message' => 'Cần ít nhất một tiêu chí assessment.'];
        $estimated = 2 + (($weighted / $weightTotal) / 5) * 3.5;
        $band = service('skillBandResolver')->resolve($estimated);
        $confidence = min(40, count($cleanAnswers) * 3);
        $this->db->transStart();
        $this->db->table('player_skill_assessments')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('discipline_id', $disciplineRow->id)->where('status', 'active')->update(['status' => 'superseded']);
        $saved = $this->db->table('player_skill_assessments')->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'discipline_id' => $disciplineRow->id, 'assessment_version' => 'v1', 'answers' => json_encode($cleanAnswers, JSON_UNESCAPED_UNICODE), 'estimated_rating' => round($estimated, 3), 'estimated_skill_band_id' => $band->id ?? null, 'confidence_score' => $confidence, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')]);
        $id = $saved ? $this->db->insertID() : 0;
        $this->db->transComplete();
        return $this->db->transStatus() && $id ? ['success' => true, 'assessment_id' => (int) $id, 'estimated_rating' => round($estimated, 3), 'estimated_skill_band' => $band->code ?? 'NR', 'confidence_score' => $confidence, 'type' => 'SELF_ASSESSMENT'] : ['success' => false, 'message' => 'Không lưu được assessment.'];
    }
}
