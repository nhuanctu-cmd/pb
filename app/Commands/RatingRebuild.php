<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RatingRebuild extends BaseCommand
{
    protected $group = 'rating';
    protected $name = 'rating:rebuild';
    protected $description = 'Rebuild canonical discipline-aware ratings from official immutable results.';
    protected $usage = 'rating:rebuild [--tenant=ID] [--player=ID] [--discipline=singles|doubles|mixed_doubles] [--dry-run]';
    protected $options = [
        '--tenant' => 'Limit to tenant ID.', '--player' => 'Limit to player ID.', '--discipline' => 'Limit to discipline.',
        '--provider' => 'Rating provider code (default internal-v1).', '--from' => 'Completed/created date lower bound.', '--to' => 'Completed/created date upper bound.', '--dry-run' => 'Calculate without changing profiles.',
    ];

    public function run(array $params)
    {
        $result = service('ratingRebuildService')->run(['tenant_id' => CLI::getOption('tenant'), 'player_id' => CLI::getOption('player'), 'discipline' => CLI::getOption('discipline'), 'provider' => CLI::getOption('provider') ?: 'internal-v1', 'from' => CLI::getOption('from'), 'to' => CLI::getOption('to'), 'dry_run' => CLI::getOption('dry-run') !== null]);
        if (empty($result['success'])) { CLI::error($result['message'] ?? 'Rating rebuild failed.'); return EXIT_ERROR; }
        CLI::write(json_encode(['dry_run' => $result['dry_run'], 'processed_matches' => $result['processed_matches'], 'skipped_matches' => $result['skipped_matches']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return EXIT_SUCCESS;
    }
}
