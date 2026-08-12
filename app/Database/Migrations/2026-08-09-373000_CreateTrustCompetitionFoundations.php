<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Additive foundation layer for governance, immutable rules, provenance,
 * provider-neutral ratings and the canonical match graph.
 *
 * Existing tables are intentionally extended without destructive rewrites.
 */
class CreateTrustCompetitionFoundations extends Migration
{
    public function up()
    {
        $this->createGovernance();
        $this->createRulesets();
        $this->createProvenance();
        $this->createProviderLinks();
        $this->createMatchGraphExtensions();
        $this->extendExistingTables();
    }

    public function down()
    {
        foreach ([
            'match_integrity_flags', 'match_sides', 'provider_rating_records',
            'player_rating_provider_links', 'data_provenance_records',
            'result_correction_requests', 'appeal_evidence', 'appeals',
            'governance_decision_evidence', 'governance_decisions',
            'sanction_conditions', 'sanction_reviews', 'draw_policy_versions',
            'seeding_policy_versions', 'eligibility_policy_versions',
            'ruleset_versions', 'rulesets', 'governance_policies',
            'governance_authorities',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createGovernance(): void
    {
        $this->table('governance_authorities', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'parent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'authority_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'country_code' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'scope_reference' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'effective_from' => ['type' => 'DATETIME', 'null' => true],
            'effective_to' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['uuid'], ['parent_id', 'authority_type', 'status'], ['country_code', 'scope_reference']]);

        $this->table('governance_policies', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'authority_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'version' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'policy_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'rules' => ['type' => 'JSON', 'null' => false],
            'content_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'effective_from' => ['type' => 'DATETIME', 'null' => false],
            'effective_to' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['authority_id', 'code', 'version'], ['policy_type', 'status', 'effective_from']]);

        $this->table('governance_decisions', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'subject_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'subject_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'authority_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'policy_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'actor_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'decision' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'reason' => ['type' => 'TEXT', 'null' => false],
            'evidence' => ['type' => 'JSON', 'null' => true],
            'audit_log_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['subject_type', 'subject_id', 'created_at'], ['authority_id', 'decision', 'created_at']]);

        $this->table('governance_decision_evidence', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'decision_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'evidence_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['decision_id', 'evidence_type']]);

        $this->table('sanction_reviews', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sanction_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'reviewer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'under_review'],
            'decision' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'reason' => ['type' => 'TEXT', 'null' => true],
            'evidence' => ['type' => 'JSON', 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['sanction_id', 'status'], ['reviewer_id', 'reviewed_at']]);

        $this->table('sanction_conditions', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sanction_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'requirement' => ['type' => 'JSON', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['sanction_id', 'code']]);

        $this->table('appeals', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'subject_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'subject_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'opened_by' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'authority_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'],
            'reason' => ['type' => 'TEXT', 'null' => false],
            'decision_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'parent_appeal_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'decided_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'decided_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['subject_type', 'subject_id', 'status'], ['tenant_id', 'status', 'created_at']]);

        $this->table('appeal_evidence', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'appeal_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'submitted_by' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'evidence_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['appeal_id', 'created_at']]);

        $this->table('result_correction_requests', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'original_result_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'requested_result' => ['type' => 'JSON', 'null' => false],
            'reason' => ['type' => 'TEXT', 'null' => false],
            'evidence' => ['type' => 'JSON', 'null' => true],
            'requester_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'reviewer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'decision_reason' => ['type' => 'TEXT', 'null' => true],
            'new_result_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['match_id', 'status'], ['original_result_version_id', 'status']]);
    }

    private function createRulesets(): void
    {
        $this->table('rulesets', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'discipline' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'pickleball'],
            'authority_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['code'], ['discipline', 'status']]);

        $this->table('ruleset_versions', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'ruleset_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'version' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'content' => ['type' => 'JSON', 'null' => false],
            'content_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => false],
            'effective_from' => ['type' => 'DATETIME', 'null' => false],
            'effective_to' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['ruleset_id', 'version'], ['ruleset_id', 'status', 'effective_from']]);

        foreach (['eligibility', 'seeding', 'draw'] as $type) {
            $this->table($type . '_policy_versions', [
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
                'version' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
                'policy' => ['type' => 'JSON', 'null' => false],
                'content_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => false],
                'effective_from' => ['type' => 'DATETIME', 'null' => false],
                'effective_to' => ['type' => 'DATETIME', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ], [['code', 'version'], ['status', 'effective_from']]);
        }
    }

    private function createProvenance(): void
    {
        $this->table('data_provenance_records', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'source_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'source_organization_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verification_level' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unverified'],
            'import_batch_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'external_reference' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'policy_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'parent_provenance_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['entity_type', 'entity_id', 'created_at'], ['source_type', 'source_id'], ['parent_provenance_id']]);
    }

    private function createProviderLinks(): void
    {
        $this->table('player_rating_provider_links', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'external_player_id' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'verification_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'consent_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'authorization_reference' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'linked_at' => ['type' => 'DATETIME', 'null' => true],
            'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
            'sync_state' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['player_id', 'provider_id'], ['provider_id', 'external_player_id'], ['sync_state', 'last_synced_at']]);

        $this->table('provider_rating_records', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'discipline' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'rating_value' => ['type' => 'DECIMAL', 'constraint' => '8,3', 'null' => true],
            'rating_label' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'external_record_id' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'observed_at' => ['type' => 'DATETIME', 'null' => false],
            'synced_at' => ['type' => 'DATETIME', 'null' => false],
            'payload' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ], [['player_id', 'provider_id', 'discipline', 'observed_at'], ['provider_id', 'external_record_id']]);
    }

    private function createMatchGraphExtensions(): void
    {
        $this->table('match_sides', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'side_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'side_order' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
            'result' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'metadata' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['match_id', 'side_code'], ['match_id', 'side_order']]);

        $this->table('match_integrity_flags', [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'flag_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'risk_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'details' => ['type' => 'JSON', 'null' => true],
            'reviewed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['match_id', 'flag_code', 'status'], ['player_id', 'status', 'risk_score']]);
    }

    private function extendExistingTables(): void
    {
        $this->addColumns('matches', [
            'source_code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'competition_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'source_organization_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'venue_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'court_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'started_at' => ['type' => 'DATETIME', 'null' => true],
            'finished_at' => ['type' => 'DATETIME', 'null' => true],
            'ruleset_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'provenance_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->addColumns('match_result_versions', [
            'ruleset_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'authority_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'provenance_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->addColumns('rating_transactions', ['provenance_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true]]);
        $this->addColumns('ranking_point_ledgers', [
            'event_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'placement' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'policy_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'provenance_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->addColumns('tournament_sanctions', [
            'authority_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'workflow_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'submitted'],
            'submitted_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'ruleset_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'policy_snapshot' => ['type' => 'JSON', 'null' => true],
        ]);
        $this->addColumns('tournaments', [
            'ruleset_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'eligibility_policy_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'seeding_policy_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'draw_policy_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'policy_snapshot' => ['type' => 'JSON', 'null' => true],
        ]);
    }

    private function table(string $name, array $fields, array $indexes = []): void
    {
        if ($this->db->tableExists($name)) return;
        $this->forge->addField($fields);
        $this->forge->addKey('id', true);
        foreach ($indexes as $index) {
            $this->forge->addKey($index);
        }
        $this->forge->createTable($name, true);
    }

    private function addColumns(string $table, array $columns): void
    {
        if (! $this->db->tableExists($table)) return;
        $missing = [];
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, $table)) $missing[$name] = $definition;
        }
        if ($missing) $this->forge->addColumn($table, $missing);
    }
}
