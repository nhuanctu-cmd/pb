<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RatingRebuild extends BaseCommand
{
    protected $group = 'rating';
    protected $name = 'rating:rebuild';
    protected $description = 'Rebuild canonical discipline-aware ratings from official immutable results.';
    protected $usage = 'rating:rebuild [--tenant=ID] [--player=ID] [--discipline=singles|doubles|mixed_doubles] [--provider=internal-v1] [--from=DATE] [--to=DATE] [--from-match=ID] [--to-match=ID] [--dry-run] [--consume] [--limit=20]';
    protected $options = [
        '--tenant' => 'Limit to tenant ID.', '--player' => 'Limit to player ID.', '--discipline' => 'Limit to discipline.',
        '--provider' => 'Rating provider code (default internal-v1).', '--from' => 'Completed/created date lower bound.', '--to' => 'Completed/created date upper bound.', '--from-match' => 'Match ID lower bound.', '--to-match' => 'Match ID upper bound.', '--dry-run' => 'Calculate without changing profiles.', '--consume' => 'Run queued rating_rebuild_jobs.', '--limit' => 'Max queued jobs to process when --consume.',
    ];

    public function run(array $params)
    {
        if (CLI::getOption('consume') !== null) {
            $result = service('ratingRebuildService')->processQueuedJobs([
                'tenant_id' => CLI::getOption('tenant'),
                'limit' => CLI::getOption('limit'),
            ]);
            if (empty($result['success'])) {
                CLI::error($result['message'] ?? 'Rebuild queue processing failed.');
                return EXIT_ERROR;
            }
            CLI::write(json_encode([
                'processed_jobs' => $result['processed_jobs'] ?? 0,
                'failed_jobs' => $result['failed_jobs'] ?? 0,
                'messages' => $result['messages'] ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return EXIT_SUCCESS;
        }

        $result = service('ratingRebuildService')->run([
            'tenant_id' => CLI::getOption('tenant'),
            'player_id' => CLI::getOption('player'),
            'discipline' => CLI::getOption('discipline'),
            'provider' => CLI::getOption('provider') ?: 'internal-v1',
            'from' => CLI::getOption('from'),
            'to' => CLI::getOption('to'),
            'from_match_id' => CLI::getOption('from-match'),
            'to_match_id' => CLI::getOption('to-match'),
            'dry_run' => CLI::getOption('dry-run') !== null,
        ]);
        if (empty($result['success'])) { CLI::error($result['message'] ?? 'Rating rebuild failed.'); return EXIT_ERROR; }
        CLI::write(json_encode([
            'dry_run' => $result['dry_run'],
            'job_id' => $result['job_id'] ?? null,
            'processed_matches' => $result['processed_matches'],
            'skipped_matches' => $result['skipped_matches'],
            'drift_count' => count($result['drift'] ?? []),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return EXIT_SUCCESS;
    }
}
