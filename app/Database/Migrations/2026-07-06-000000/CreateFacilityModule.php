<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFacilityModule extends Migration
{
    public function up()
    {
        // ========== 1. FACILITIES (Cluster Management) ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description_vi'    => ['type' => 'TEXT', 'null' => true],
            'description_en'    => ['type' => 'TEXT', 'null' => true],
            'address'           => ['type' => 'TEXT', 'null' => true],
            'city'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'district'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'latitude'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'longitude'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'phone'             => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'website'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'logo'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cover_image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'timezone'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Asia/Ho_Chi_Minh'],
            'currency'          => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'VND'],
            'total_courts'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'total_branches'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'suspended'], 'default' => 'active'],
            'sort_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'meta'              => ['type' => 'JSON', 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('facilities', true);

        // ========== 2. BRANCHES (Upgraded) ==========
        // Add facility_id and new columns to existing branches table
        $this->forge->addColumn('branches', [
            'facility_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            'branch_type'       => ['type' => 'ENUM', 'constraint' => ['main', 'sub', 'mini', 'partner'], 'default' => 'sub', 'after' => 'code'],
            'total_courts'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'longitude'],
            'indoor_courts'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'total_courts'],
            'outdoor_courts'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'indoor_courts'],
            'has_parking'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'outdoor_courts'],
            'has_canteen'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'has_parking'],
            'has_locker'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'has_canteen'],
            'has_shower'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'has_locker'],
            'has_wifi'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'has_shower'],
            'opening_date'      => ['type' => 'DATE', 'null' => true, 'after' => 'has_wifi'],
            'images'            => ['type' => 'JSON', 'null' => true, 'after' => 'opening_date'],
            'settings'          => ['type' => 'JSON', 'null' => true, 'after' => 'images'],
        ]);
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE', 'fk_branch_facility');

        // ========== 3. COURT_STATUSES (Status configuration) ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'color'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#6c757d'],
            'icon'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_bookable'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('court_statuses', true);

        // ========== 4. COURTS (Upgraded - add status_id, facility_id, amenities) ==========
        $this->forge->addColumn('courts', [
            'facility_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            'status_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'court_type_id'],
            'surface_type'      => ['type' => 'ENUM', 'constraint' => ['hard', 'clay', 'grass', 'acrylic', 'cushion', 'other'], 'default' => 'hard', 'after' => 'is_indoor'],
            'length'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'area'],
            'width'             => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'length'],
            'ceiling_height'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'width'],
            'player_capacity'   => ['type' => 'INT', 'constraint' => 11, 'default' => 4, 'after' => 'ceiling_height'],
            'spectator_capacity'=> ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'player_capacity'],
            'color_scheme'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'spectator_capacity'],
            'display_name'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'name_en'],
            'coordinates_x'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'sort_order'],
            'coordinates_y'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'coordinates_x'],
            'rotation'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'coordinates_y'],  // For map view
            'amenities'         => ['type' => 'JSON', 'null' => true, 'after' => 'rotation'],
            'pricing_rules'     => ['type' => 'JSON', 'null' => true, 'after' => 'amenities'],
            'last_active_at'    => ['type' => 'DATETIME', 'null' => true, 'after' => 'pricing_rules'],
        ]);
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE', 'fk_court_facility');
        $this->forge->addForeignKey('status_id', 'court_statuses', 'id', 'SET NULL', 'CASCADE', 'fk_court_status');

        // ========== 5. COURT_DEVICES (IoT Device Management) ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'facility_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'device_type'       => ['type' => 'ENUM', 'constraint' => [
                'light', 'fan', 'camera', 'locker', 'speaker',
                'scoreboard', 'sensor_temp', 'sensor_humidity',
                'gate', 'ac', 'projector', 'other'
            ]],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'name_vi'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'model'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'serial_number'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_address'        => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'mac_address'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'firmware_version'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'mqtt_topic'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'api_endpoint'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'config'            => ['type' => 'JSON', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['online', 'offline', 'error', 'disabled'], 'default' => 'offline'],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_ping_at'      => ['type' => 'DATETIME', 'null' => true],
            'last_value'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('court_devices', true);

        // ========== 6. COURT_DEVICE_LOGS ==========
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'device_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'action'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'previous_value'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'triggered_by'      => ['type' => 'ENUM', 'constraint' => ['manual', 'schedule', 'sensor', 'api', 'system'], 'default' => 'system'],
            'triggered_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'metadata'          => ['type' => 'JSON', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['device_id', 'created_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('device_id', 'court_devices', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('court_device_logs', true);

        // ========== 7. COURT_MAINTENANCE (Upgraded) ==========
        $this->forge->addColumn('court_maintenance', [
            'facility_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            'maintenance_type'  => ['type' => 'ENUM', 'constraint' => ['routine', 'emergency', 'renovation', 'inspection', 'other'], 'default' => 'routine', 'after' => 'court_id'],
            'priority'          => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium', 'after' => 'maintenance_type'],
            'title_vi'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'priority'],
            'title_en'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'title_vi'],
            'notes'             => ['type' => 'TEXT', 'null' => true, 'after' => 'title_en'],
            'assigned_to'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'notes'],
            'completed_at'      => ['type' => 'DATETIME', 'null' => true, 'after' => 'assigned_to'],
            'cost_estimate'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'completed_at'],
            'actual_cost'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'cost_estimate'],
            'images_before'     => ['type' => 'JSON', 'null' => true, 'after' => 'actual_cost'],
            'images_after'      => ['type' => 'JSON', 'null' => true, 'after' => 'images_before'],
        ]);
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE', 'fk_maint_facility');
        $this->forge->addForeignKey('assigned_to', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_maint_assigned');

        // ========== 8. COURT_SESSIONS (Realtime tracking) ==========
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'booking_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'start_time'        => ['type' => 'DATETIME'],
            'expected_end_time' => ['type' => 'DATETIME'],
            'actual_end_time'   => ['type' => 'DATETIME', 'null' => true],
            'player_count'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'player_names'      => ['type' => 'JSON', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'extended', 'completed', 'cancelled'], 'default' => 'active'],
            'is_overtime'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'overtime_minutes'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'delay_minutes'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'checked_in_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['branch_id', 'court_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('court_sessions', true);

        // ========== 9. BRANCH_MEDIA ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'media_type'        => ['type' => 'ENUM', 'constraint' => ['image', 'video', 'panorama', '360']],
            'file_path'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'title_vi'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'title_en'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_primary'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('branch_media', true);

        // ========== SEED DEFAULT COURT STATUSES ==========
        $this->db->table('court_statuses')->insertBatch([
            ['tenant_id' => 1, 'code' => 'available',   'name_vi' => 'Sân trống',     'name_en' => 'Available',   'color' => '#28a745', 'icon' => 'bi-check-circle',   'is_bookable' => 1, 'sort_order' => 1],
            ['tenant_id' => 1, 'code' => 'occupied',    'name_vi' => 'Đang chơi',     'name_en' => 'Occupied',    'color' => '#ffc107', 'icon' => 'bi-play-circle',   'is_bookable' => 0, 'sort_order' => 2],
            ['tenant_id' => 1, 'code' => 'booked',      'name_vi' => 'Đã đặt',        'name_en' => 'Booked',      'color' => '#17a2b8', 'icon' => 'bi-calendar-check', 'is_bookable' => 0, 'sort_order' => 3],
            ['tenant_id' => 1, 'code' => 'maintenance', 'name_vi' => 'Bảo trì',       'name_en' => 'Maintenance', 'color' => '#dc3545', 'icon' => 'bi-tools',         'is_bookable' => 0, 'sort_order' => 4],
            ['tenant_id' => 1, 'code' => 'cleaning',    'name_vi' => 'Đang dọn vệ sinh', 'name_en' => 'Cleaning',  'color' => '#6f42c1', 'icon' => 'bi-broom',         'is_bookable' => 0, 'sort_order' => 5],
            ['tenant_id' => 1, 'code' => 'inactive',    'name_vi' => 'Ngưng hoạt động', 'name_en' => 'Inactive',   'color' => '#6c757d', 'icon' => 'bi-x-circle',      'is_bookable' => 0, 'sort_order' => 6],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('court_sessions', true);
        $this->forge->dropTable('branch_media', true);
        $this->forge->dropTable('court_device_logs', true);
        $this->forge->dropTable('court_devices', true);

        $this->forge->dropColumn('court_maintenance', ['facility_id', 'maintenance_type', 'priority', 'title_vi', 'title_en', 'notes', 'assigned_to', 'completed_at', 'cost_estimate', 'actual_cost', 'images_before', 'images_after']);

        $this->forge->dropColumn('courts', ['facility_id', 'status_id', 'surface_type', 'length', 'width', 'ceiling_height', 'player_capacity', 'spectator_capacity', 'color_scheme', 'display_name', 'coordinates_x', 'coordinates_y', 'rotation', 'amenities', 'pricing_rules', 'last_active_at']);

        $this->forge->dropTable('court_statuses', true);

        $this->forge->dropColumn('branches', ['facility_id', 'branch_type', 'total_courts', 'indoor_courts', 'outdoor_courts', 'has_parking', 'has_canteen', 'has_locker', 'has_shower', 'has_wifi', 'opening_date', 'images', 'settings']);

        $this->forge->dropTable('facilities', true);
    }
}
