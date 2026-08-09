<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingSystemUpgrade extends Migration
{
    public function up()
    {
        // ========== 1. BOOKING_STATUSES (Configurable) ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'color'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#6c757d'],
            'icon'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
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
        $this->forge->createTable('booking_statuses', true);

        // ========== 2. BOOKINGS (Upgraded) ==========
        $this->forge->addColumn('bookings', [
            'facility_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            'status_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'status'],
            'hold_until'            => ['type' => 'DATETIME', 'null' => true, 'after' => 'expires_at'],
            'is_hold'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'hold_until'],
            'timeout_minutes'       => ['type' => 'INT', 'constraint' => 11, 'default' => 15, 'after' => 'is_hold'],
            'auto_release_at'       => ['type' => 'DATETIME', 'null' => true, 'after' => 'timeout_minutes'],
            'discount_amount'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'paid_amount'],
            'tax_amount'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'discount_amount'],
            'surcharge_amount'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'tax_amount'],
            'net_amount'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'surcharge_amount'],
            'refund_amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'net_amount'],
            'refund_policy'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'refund_amount'],
            'cancellation_policy'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'refund_policy'],
            'pricing_rule_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'cancellation_policy'],
            'price_breakdown'       => ['type' => 'JSON', 'null' => true, 'after' => 'pricing_rule_id'],
            'player_count'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'price_breakdown'],
            'is_recurring'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'player_count'],
            'recurring_pattern'     => ['type' => 'JSON', 'null' => true, 'after' => 'is_recurring'],
            'recurring_parent_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'recurring_pattern'],
            'membership_discount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'recurring_parent_id'],
            'platform_fee'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'membership_discount'],
            'check_in_window_start' => ['type' => 'DATETIME', 'null' => true, 'after' => 'completed_at'],
            'check_in_window_end'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'check_in_window_start'],
            'reminder_sent_at'      => ['type' => 'DATETIME', 'null' => true, 'after' => 'check_in_window_end'],
            'rating'                => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'reminder_sent_at'],
            'feedback'              => ['type' => 'TEXT', 'null' => true, 'after' => 'rating'],
        ]);
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE', 'fk_booking_facility');
        $this->forge->addForeignKey('status_id', 'booking_statuses', 'id', 'SET NULL', 'CASCADE', 'fk_booking_status');
        $this->forge->addForeignKey('recurring_parent_id', 'bookings', 'id', 'SET NULL', 'CASCADE', 'fk_booking_parent');
        $this->forge->addForeignKey('pricing_rule_id', 'pricing_rules', 'id', 'SET NULL', 'CASCADE', 'fk_booking_pricing');

        // ========== 3. BOOKING_ITEMS (Upgraded) ==========
        $this->forge->addColumn('booking_items', [
            'facility_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            'court_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'court_id'],
            'court_type_name'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'court_name'],
            'date'              => ['type' => 'DATE', 'null' => true, 'after' => 'end_time'],
            'base_price'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'price'],
            'dynamic_price'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'base_price'],
            'surcharge'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'dynamic_price'],
            'discount'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'surcharge'],
            'pricing_detail'    => ['type' => 'JSON', 'null' => true, 'after' => 'discount'],
            'item_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'pricing_detail'],
        ]);
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE', 'fk_bi_facility');

        // ========== 4. PRICING_RULES ==========
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'facility_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'branch_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'court_type_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'code'                  => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'           => ['type' => 'TEXT', 'null' => true],
            'rule_type'             => ['type' => 'ENUM', 'constraint' => [
                'base_price', 'hourly_rate', 'daily_rate', 'weekend_rate',
                'holiday_rate', 'member_rate', 'happy_hour', 'ai_suggested',
                'surge_pricing', 'early_bird', 'last_minute', 'package_deal',
                'seasonal', 'promotion'
            ]],
            'priority'              => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'apply_order'           => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'calculation_method'    => ['type' => 'ENUM', 'constraint' => ['fixed', 'percentage', 'formula'], 'default' => 'fixed'],
            'price_adjustment'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'price_multiplier'      => ['type' => 'DECIMAL', 'constraint' => '10,6', 'default' => 1.0],
            'min_price'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'max_price'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'currency'              => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'VND'],
            'is_active'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_ai_generated'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ai_confidence_score'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'apply_to_members'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'min_duration_minutes'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'max_duration_minutes'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'min_advance_hours'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'max_advance_days'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'valid_from'            => ['type' => 'DATETIME', 'null' => true],
            'valid_to'              => ['type' => 'DATETIME', 'null' => true],
            'usage_limit'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'usage_count'           => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_type_id', 'court_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pricing_rules', true);

        // ========== 5. PRICING_RULE_CONDITIONS ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pricing_rule_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'condition_type'    => ['type' => 'ENUM', 'constraint' => [
                'day_of_week', 'time_of_day', 'date_range', 'holiday',
                'occupancy_rate', 'advance_days', 'duration_minutes',
                'member_tier', 'booking_source', 'court_type',
                'branch_id', 'season', 'weather', 'special_event'
            ]],
            'operator'          => ['type' => 'ENUM', 'constraint' => [
                'equals', 'not_equals', 'greater_than', 'less_than',
                'between', 'in', 'not_in', 'contains'
            ]],
            'value'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'value_to'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pricing_rule_id', 'pricing_rules', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pricing_rule_conditions', true);

        // ========== 6. DYNAMIC_PRICE_LOGS ==========
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'pricing_rule_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'court_type_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'date'              => ['type' => 'DATE'],
            'time_slot'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'base_price'        => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'calculated_price'  => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'final_price'       => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'applied_rules'     => ['type' => 'JSON'],
            'occupancy_rate'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'ai_generated'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'date', 'branch_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pricing_rule_id', 'pricing_rules', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('dynamic_price_logs', true);

        // ========== 7. BOOKING_RECURRING_TEMPLATES ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'start_date'        => ['type' => 'DATE'],
            'end_date'          => ['type' => 'DATE', 'null' => true],
            'start_time'        => ['type' => 'TIME'],
            'end_time'          => ['type' => 'TIME'],
            'duration_minutes'  => ['type' => 'INT', 'constraint' => 11],
            'repeat_type'       => ['type' => 'ENUM', 'constraint' => ['daily', 'weekly', 'biweekly', 'monthly', 'custom']],
            'repeat_interval'   => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'repeat_days'       => ['type' => 'JSON', 'null' => true],
            'exclude_dates'     => ['type' => 'JSON', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'completed', 'cancelled'], 'default' => 'active'],
            'total_occurrences' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'completed_occurrences' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'next_occurrence'   => ['type' => 'DATE', 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_recurring_templates', true);

        // ========== SEED DEFAULT BOOKING STATUSES ==========
        $this->db->table('booking_statuses')->insertBatch([
            ['tenant_id' => 1, 'code' => 'draft',       'name_vi' => 'Nháp',          'name_en' => 'Draft',       'color' => '#6c757d', 'icon' => 'bi-file-text',     'sort_order' => 1],
            ['tenant_id' => 1, 'code' => 'pending',     'name_vi' => 'Chờ xác nhận',  'name_en' => 'Pending',     'color' => '#ffc107', 'icon' => 'bi-clock',         'sort_order' => 2],
            ['tenant_id' => 1, 'code' => 'hold',        'name_vi' => 'Giữ sân tạm',   'name_en' => 'On Hold',     'color' => '#fd7e14', 'icon' => 'bi-pause-circle',  'sort_order' => 3],
            ['tenant_id' => 1, 'code' => 'reserved',    'name_vi' => 'Đã đặt cọc',    'name_en' => 'Reserved',    'color' => '#17a2b8', 'icon' => 'bi-calendar-check', 'sort_order' => 4],
            ['tenant_id' => 1, 'code' => 'paid',        'name_vi' => 'Đã thanh toán', 'name_en' => 'Paid',        'color' => '#0d6efd', 'icon' => 'bi-credit-card',   'sort_order' => 5],
            ['tenant_id' => 1, 'code' => 'checked_in',  'name_vi' => 'Đã check-in',   'name_en' => 'Checked In',  'color' => '#198754', 'icon' => 'bi-box-arrow-in-right', 'sort_order' => 6],
            ['tenant_id' => 1, 'code' => 'in_progress', 'name_vi' => 'Đang chơi',     'name_en' => 'In Progress', 'color' => '#0dcaf0', 'icon' => 'bi-play-circle',   'sort_order' => 7],
            ['tenant_id' => 1, 'code' => 'completed',   'name_vi' => 'Hoàn thành',    'name_en' => 'Completed',   'color' => '#198754', 'icon' => 'bi-check-circle',  'sort_order' => 8],
            ['tenant_id' => 1, 'code' => 'cancelled',   'name_vi' => 'Đã hủy',        'name_en' => 'Cancelled',   'color' => '#dc3545', 'icon' => 'bi-x-circle',       'sort_order' => 9],
            ['tenant_id' => 1, 'code' => 'refunded',    'name_vi' => 'Đã hoàn tiền',  'name_en' => 'Refunded',    'color' => '#6c757d', 'icon' => 'bi-arrow-return-left', 'sort_order' => 10],
            ['tenant_id' => 1, 'code' => 'no_show',     'name_vi' => 'Không đến',     'name_en' => 'No Show',     'color' => '#dc3545', 'icon' => 'bi-person-x',       'sort_order' => 11],
            ['tenant_id' => 1, 'code' => 'expired',     'name_vi' => 'Hết hạn',       'name_en' => 'Expired',     'color' => '#6c757d', 'icon' => 'bi-clock-history',  'sort_order' => 12],
        ]);

        // ========== SEED DEFAULT PRICING RULES ==========
        $this->db->table('pricing_rules')->insertBatch([
            [
                'tenant_id'          => 1,
                'code'               => 'BASE_HOURLY',
                'name_vi'            => 'Giá cơ bản theo giờ',
                'name_en'            => 'Base Hourly Rate',
                'rule_type'          => 'base_price',
                'priority'           => 1,
                'apply_order'        => 1,
                'calculation_method' => 'fixed',
                'price_adjustment'   => 150000,
                'price_multiplier'   => 1.0,
                'currency'           => 'VND',
                'is_active'          => 1,
            ],
            [
                'tenant_id'          => 1,
                'code'               => 'WEEKEND_SURGE',
                'name_vi'            => 'Phụ thu cuối tuần',
                'name_en'            => 'Weekend Surcharge',
                'rule_type'          => 'weekend_rate',
                'priority'           => 2,
                'apply_order'        => 10,
                'calculation_method' => 'percentage',
                'price_adjustment'   => 20,
                'price_multiplier'   => 1.2,
                'currency'           => 'VND',
                'is_active'          => 1,
            ],
            [
                'tenant_id'          => 1,
                'code'               => 'HAPPY_HOUR_MORNING',
                'name_vi'            => 'Giờ vàng sáng sớm (6-9h)',
                'name_en'            => 'Happy Hour Morning (6-9AM)',
                'rule_type'          => 'happy_hour',
                'priority'           => 3,
                'apply_order'        => 5,
                'calculation_method' => 'percentage',
                'price_adjustment'   => -30,
                'price_multiplier'   => 0.7,
                'currency'           => 'VND',
                'is_active'          => 1,
            ],
            [
                'tenant_id'          => 1,
                'code'               => 'MEMBER_DISCOUNT',
                'name_vi'            => 'Giảm giá hội viên',
                'name_en'            => 'Member Discount',
                'rule_type'          => 'member_rate',
                'priority'           => 4,
                'apply_order'        => 15,
                'calculation_method' => 'percentage',
                'price_adjustment'   => -15,
                'price_multiplier'   => 0.85,
                'currency'           => 'VND',
                'is_active'          => 1,
                'apply_to_members'   => 1,
            ],
            [
                'tenant_id'          => 1,
                'code'               => 'PEAK_SURGE',
                'name_vi'            => 'Phụ thu giờ cao điểm (17-21h)',
                'name_en'            => 'Peak Hour Surge (5-9PM)',
                'rule_type'          => 'surge_pricing',
                'priority'           => 5,
                'apply_order'        => 20,
                'calculation_method' => 'percentage',
                'price_adjustment'   => 50,
                'price_multiplier'   => 1.5,
                'currency'           => 'VND',
                'is_active'          => 1,
            ],
        ]);

        // ========== SEED PRICING RULE CONDITIONS ==========
        $this->db->table('pricing_rule_conditions')->insertBatch([
            // Weekend: day_of_week in [6,7] (Sat, Sun)
            ['pricing_rule_id' => 2, 'condition_type' => 'day_of_week', 'operator' => 'in', 'value' => '6,7'],
            // Happy hour: time_of_day between 06:00-09:00
            ['pricing_rule_id' => 3, 'condition_type' => 'time_of_day', 'operator' => 'between', 'value' => '06:00', 'value_to' => '09:00'],
            // Peak surge: time_of_day between 17:00-21:00
            ['pricing_rule_id' => 5, 'condition_type' => 'time_of_day', 'operator' => 'between', 'value' => '17:00', 'value_to' => '21:00'],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('booking_recurring_templates', true);
        $this->forge->dropTable('dynamic_price_logs', true);
        $this->forge->dropTable('pricing_rule_conditions', true);
        $this->forge->dropTable('pricing_rules', true);

        $this->forge->dropColumn('booking_items', ['facility_id', 'court_name', 'court_type_name', 'date', 'base_price', 'dynamic_price', 'surcharge', 'discount', 'pricing_detail', 'item_order']);

        $this->forge->dropColumn('bookings', [
            'facility_id', 'status_id', 'hold_until', 'is_hold', 'timeout_minutes', 'auto_release_at',
            'discount_amount', 'tax_amount', 'surcharge_amount', 'net_amount',
            'refund_amount', 'refund_policy', 'cancellation_policy',
            'pricing_rule_id', 'price_breakdown', 'player_count',
            'is_recurring', 'recurring_pattern', 'recurring_parent_id',
            'membership_discount', 'platform_fee',
            'check_in_window_start', 'check_in_window_end',
            'reminder_sent_at', 'rating', 'feedback',
        ]);

        $this->forge->dropTable('booking_statuses', true);
    }
}
