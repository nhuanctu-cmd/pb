<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RankingRebuild extends BaseCommand
{
    protected $group = 'ranking';
    protected $name = 'ranking:rebuild';
    protected $description = 'Rebuild ranking ledger from official immutable results.';
    protected $usage = 'ranking:rebuild [--tenant=ID] [--authority=CODE] [--snapshot-date=YYYY-MM-DD] [--dry-run]';
    protected $options = ['--tenant' => 'Limit to tenant ID.', '--authority' => 'Ranking authority code.', '--snapshot-date' => 'Snapshot date.', '--dry-run' => 'Calculate without inserting missing ledger rows.'];

    public function run(array $params)
    {
        $result = service('rankingRebuildService')->run([
            'tenant_id' => CLI::getOption('tenant'), 'authority' => CLI::getOption('authority') ?: 'national-pickleball',
            'snapshot_date' => CLI::getOption('snapshot-date'), 'dry_run' => CLI::getOption('dry-run') !== null,
        ]);
        if (empty($result['success'])) { CLI::error($result['message'] ?? 'Ranking rebuild failed.'); return EXIT_ERROR; }
        CLI::write(json_encode(['dry_run' => $result['dry_run'], 'processed_matches' => $result['processed_matches'], 'missing_entries' => $result['missing_entries'], 'created_entries' => $result['created_entries'], 'drift' => count($result['drift'])], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return EXIT_SUCCESS;
    }
}
