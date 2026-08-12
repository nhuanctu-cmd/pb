<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Canonical, discipline-aware rating layer. Legacy ELO tables are preserved. */
class CreateRatingEngineV1 extends Migration
{
    public function up()
    {
        $this->createDisciplines();
        $this->createSkillBands();
        $this->createPolicyTables();
        $this->createProfiles();
        $this->createTransactions();
        $this->createReliabilitySnapshots();
        $this->createSkillClaims();
        $this->createAssessments();
        $this->createIntegrityFlags();
        $this->seedDefaults();
    }

    public function down()
    {
        foreach (['rating_integrity_flags', 'player_skill_assessments', 'club_player_skill_assessments', 'player_skill_claims', 'rating_reliability_snapshots', 'rating_transactions', 'player_rating_profiles', 'rating_match_type_weights', 'rating_policy_versions', 'skill_level_bands', 'rating_disciplines'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createDisciplines(): void
    {
        if ($this->db->tableExists('rating_disciplines')) return;
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('rating_disciplines', true);
    }

    private function createSkillBands(): void
    {
        if ($this->db->tableExists('skill_level_bands')) return;
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'min_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'max_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'display_order' => ['type' => 'INT', 'default' => 0],
            'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['min_rating', 'max_rating', 'active']);
        $this->forge->createTable('skill_level_bands', true);
    }

    private function createPolicyTables(): void
    {
        if (! $this->db->tableExists('rating_policy_versions')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'discipline_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'version' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
                'effective_from' => ['type' => 'DATETIME', 'null' => false],
                'effective_to' => ['type' => 'DATETIME', 'null' => true],
                'configuration' => ['type' => 'JSON', 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'retired'], 'default' => 'draft'],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['provider_id', 'discipline_id', 'version']);
            $this->forge->addKey(['provider_id', 'discipline_id', 'status', 'effective_from']);
            $this->forge->createTable('rating_policy_versions', true);
        }
        if (! $this->db->tableExists('rating_match_type_weights')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'policy_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'match_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
                'weight' => ['type' => 'DECIMAL', 'constraint' => '5,3', 'null' => false],
                'eligible' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['policy_version_id', 'match_type']);
            $this->forge->createTable('rating_match_type_weights', true);
        }
    }

    private function createProfiles(): void
    {
        if ($this->db->tableExists('player_rating_profiles')) return;
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'discipline_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'rating_value' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'skill_band_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reliability_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['nr', 'provisional', 'established', 'inactive', 'under_review', 'suspended'], 'default' => 'nr'],
            'rated_match_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'verified_match_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'last_rated_match_at' => ['type' => 'DATETIME', 'null' => true],
            'highest_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'lowest_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'established_at' => ['type' => 'DATETIME', 'null' => true],
            'calculated_at' => ['type' => 'DATETIME', 'null' => true],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'player_id', 'provider_id', 'discipline_id']);
        $this->forge->addKey(['tenant_id', 'discipline_id', 'status', 'rating_value']);
        $this->forge->createTable('player_rating_profiles', true);
    }

    private function createTransactions(): void
    {
        if ($this->db->tableExists('rating_transactions')) return;
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'discipline_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'match_result_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'rating_policy_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'transaction_type' => ['type' => 'ENUM', 'constraint' => ['impact', 'reversal', 'replacement', 'adjustment', 'seed'], 'default' => 'impact'],
            'before_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'after_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
            'rating_delta' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => false],
            'expected_performance' => ['type' => 'DECIMAL', 'constraint' => '6,4', 'null' => true],
            'actual_performance' => ['type' => 'DECIMAL', 'constraint' => '6,4', 'null' => true],
            'reliability_before' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'reliability_after' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'match_weight' => ['type' => 'DECIMAL', 'constraint' => '5,3', 'default' => 1],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'status' => ['type' => 'ENUM', 'constraint' => ['applied', 'reversed', 'voided'], 'default' => 'applied'],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 220, 'null' => false],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('idempotency_key');
        $this->forge->addKey(['tenant_id', 'player_id', 'discipline_id', 'created_at']);
        $this->forge->addKey(['match_id', 'match_result_version_id', 'status']);
        $this->forge->createTable('rating_transactions', true);
    }

    private function createReliabilitySnapshots(): void
    {
        if ($this->db->tableExists('rating_reliability_snapshots')) return;
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'profile_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'rating_transaction_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
            'components' => ['type' => 'JSON', 'null' => false],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'as_of' => ['type' => 'DATETIME', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['profile_id', 'as_of']);
        $this->forge->createTable('rating_reliability_snapshots', true);
    }

    private function createSkillClaims(): void
    {
        if (! $this->db->tableExists('player_skill_claims')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'discipline_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'source_type' => ['type' => 'ENUM', 'constraint' => ['self', 'club', 'coach', 'tournament_organizer', 'external_provider'], 'null' => false],
                'source_organization_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'source_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'claimed_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
                'skill_band_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'external_provider' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'external_reference' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'verification_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'verified', 'rejected', 'expired'], 'default' => 'pending'],
                'evidence' => ['type' => 'JSON', 'null' => true],
                'claimed_at' => ['type' => 'DATETIME', 'null' => false],
                'expires_at' => ['type' => 'DATETIME', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['player_id', 'discipline_id', 'verification_status']);
            $this->forge->addKey(['source_type', 'source_organization_id']);
            $this->forge->createTable('player_skill_claims', true);
        }
        if (! $this->db->tableExists('club_player_skill_assessments')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'club_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'discipline_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'skill_band_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'rating_value' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => true],
                'assessed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'verification_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'verified', 'rejected'], 'default' => 'pending'],
                'evidence' => ['type' => 'JSON', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'club_id', 'player_id', 'discipline_id']);
            $this->forge->createTable('club_player_skill_assessments', true);
        }
    }

    private function createAssessments(): void
    {
        if ($this->db->tableExists('player_skill_assessments')) return;
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'discipline_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'assessment_version' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'v1'],
            'answers' => ['type' => 'JSON', 'null' => false],
            'estimated_rating' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'null' => false],
            'estimated_skill_band_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'confidence_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'superseded'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['player_id', 'discipline_id', 'status']);
        $this->forge->createTable('player_skill_assessments', true);
    }

    private function createIntegrityFlags(): void
    {
        if ($this->db->tableExists('rating_integrity_flags')) return;
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'risk_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'approved', 'rejected', 'blocked'], 'default' => 'open'],
            'details' => ['type' => 'JSON', 'null' => true],
            'resolved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'risk_score']);
        $this->forge->addKey(['match_id', 'player_id', 'code']);
        $this->forge->createTable('rating_integrity_flags', true);
    }

    private function seedDefaults(): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ([
            ['singles', 'Singles'], ['doubles', 'Doubles'], ['mixed_doubles', 'Mixed Doubles'],
        ] as [$code, $name]) {
            if (! $this->db->table('rating_disciplines')->where('code', $code)->countAllResults()) {
                $this->db->table('rating_disciplines')->insert(['code' => $code, 'name' => $name, 'active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $bands = [
            ['NR', 'Not Rated', null, null], ['2.0', 'Developing', 2.000, 2.499], ['2.5', 'Beginner', 2.500, 2.999],
            ['3.0', 'Advanced Beginner', 3.000, 3.499], ['3.5', 'Intermediate', 3.500, 3.999], ['4.0', 'Advanced Intermediate', 4.000, 4.499],
            ['4.5', 'Advanced', 4.500, 4.999], ['5.0', 'Expert', 5.000, 5.499], ['5.5+', 'Elite / Pro', 5.500, null],
        ];
        foreach ($bands as $order => [$code, $name, $min, $max]) {
            if (! $this->db->table('skill_level_bands')->where('code', $code)->countAllResults()) {
                $this->db->table('skill_level_bands')->insert(['code' => $code, 'name' => $name, 'min_rating' => $min, 'max_rating' => $max, 'display_order' => $order, 'active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow();
        if (! $provider) {
            $this->db->table('rating_providers')->insert(['code' => 'internal-v1', 'name' => 'Internal Pickleball Rating V1', 'provider_type' => 'internal', 'status' => 'active', 'config' => json_encode(['scale' => '2.0-5.5', 'discipline_aware' => true]), 'created_at' => $now, 'updated_at' => $now]);
            $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow();
        }
        $configuration = [
            'initial_rating' => 3.000, 'base_delta' => 0.160, 'max_delta' => 0.350, 'expected_rating_divisor' => 2.0, 'established_reliability' => 70,
            'provisional_volatility' => 1.35, 'score_margin_impact' => 0.15, 'recency_half_life_days' => 365,
            'reliability_weights' => ['volume' => 0.30, 'verification' => 0.25, 'recency' => 0.20, 'opponent_diversity' => 0.15, 'competition_diversity' => 0.10],
            'team_strategy' => 'TEAM_AVERAGE', 'skill_band_hysteresis' => 0.05, 'allow_play_up' => true, 'allow_play_down' => false,
        ];
        $disciplines = $this->db->table('rating_disciplines')->where('active', 1)->get()->getResult();
        foreach ($disciplines as $discipline) {
            if (! $provider || $this->db->table('rating_policy_versions')->where('provider_id', $provider->id)->where('discipline_id', $discipline->id)->where('version', '1.0')->countAllResults()) continue;
            $this->db->table('rating_policy_versions')->insert(['provider_id' => $provider->id, 'discipline_id' => $discipline->id, 'name' => 'Internal Pickleball Rating', 'version' => '1.0', 'effective_from' => $now, 'configuration' => json_encode($configuration), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
            $policyId = $this->db->insertID();
            foreach (['self_reported' => 0.50, 'club_verified' => 0.75, 'league_verified' => 0.90, 'tournament_verified' => 1.00, 'official' => 1.00] as $type => $weight) {
                $this->db->table('rating_match_type_weights')->insert(['policy_version_id' => $policyId, 'match_type' => $type, 'weight' => $weight, 'eligible' => $weight > 0, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
