<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Cover the hot predicates used by weekly court availability. */
class AddAvailabilityPerformanceIndexes extends Migration
{
    private array $indexes = [
        ['courts', 'idx_courts_tenant_branch_status', '(`tenant_id`,`branch_id`,`status`,`deleted_at`)'],
        ['booking_items', 'idx_booking_items_court_status_time', '(`court_id`,`status`,`start_time`,`end_time`,`booking_id`)'],
        ['bookings', 'idx_bookings_tenant_date_status_deleted', '(`tenant_id`,`booking_date`,`status`,`deleted_at`)'],
        ['court_maintenance', 'idx_maintenance_court_status_window', '(`court_id`,`status`,`start_time`,`end_time`)'],
        ['branch_opening_hours', 'idx_opening_branch_day_closed', '(`branch_id`,`day_of_week`,`is_closed`)'],
        ['branch_holidays', 'idx_holidays_branch_date_closed', '(`branch_id`,`holiday_date`,`is_closed`)'],
    ];

    public function up()
    {
        foreach ($this->indexes as [$table, $name, $columns]) {
            if (! $this->db->tableExists($table) || $this->indexExists($table, $name)) continue;
            $this->db->query("CREATE INDEX `{$name}` ON `{$table}` {$columns}");
        }
    }

    public function down()
    {
        foreach ($this->indexes as [$table, $name]) {
            if ($this->db->tableExists($table) && $this->indexExists($table, $name)) $this->db->query("DROP INDEX `{$name}` ON `{$table}`");
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$name])->getRow() !== null;
    }
}
