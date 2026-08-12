<?php

namespace App\Filters;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * Bootstrap the local Laragon environment on the first browser request.
 *
 * This is opt-in through app.autoBootstrap and disabled for CLI/test requests.
 * A file lock prevents two browser tabs from running setup concurrently.
 */
class AutoBootstrapFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli() || ! filter_var(env('app.autoBootstrap', false), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $lockPath = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'app-bootstrap.lock';
        $handle = @fopen($lockPath, 'c');
        if (! $handle || ! @flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) @fclose($handle);
            return null;
        }

        try {
            $db = Database::connect();
            if (! $this->schemaReady($db)) {
                service('migrations')->latest();
            }

            $db->close();
            $db = Database::connect();
            if (! $this->demoReady($db)) {
                $seeder = new Seeder(config('Database'), $db);
                $seeder->setSilent(true)->call('App\\Database\\Seeds\\CommercialDemoSeeder');
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Automatic Laragon bootstrap failed: ' . $exception->getMessage());
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function schemaReady(object $db): bool
    {
        foreach (['migrations', 'tenants', 'players', 'tournaments', 'tournament_matches', 'tournament_templates', 'daily_closings', 'crm_campaigns', 'crm_campaign_recipients'] as $table) {
            if (! $db->tableExists($table)) return false;
        }
        return true;
    }

    private function demoReady(object $db): bool
    {
        try {
            return $db->table('tenants')->where('status', 'active')->countAllResults() > 0
                && $db->table('players')->where('status', 'active')->countAllResults() >= 100
                && $db->table('tournaments')->where('deleted_at', null)->countAllResults() > 0
                && $db->table('tournament_matches')->countAllResults() > 0
                && $db->table('tournament_templates')->where('is_active', 1)->countAllResults() > 0
                && $db->table('daily_closings')->countAllResults() > 0
                && $db->table('crm_campaigns')->countAllResults() > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
