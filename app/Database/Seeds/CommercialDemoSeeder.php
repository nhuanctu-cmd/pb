<?php

namespace App\Database\Seeds;

use App\Services\TenantPlanService;
use App\Services\WebhookService;
use CodeIgniter\Database\Seeder;

/**
 * Bộ dữ liệu demo thương mại cho toàn bộ Pickleball System.
 *
 * Chạy: php spark db:seed CommercialDemoSeeder
 *
 * Seeder cố ý idempotent: dùng mã/code ổn định, không truncate dữ liệu người dùng.
 * Dữ liệu nghiệp vụ được tạo trong toàn bộ tenant active để mọi tenant đều có
 * thể xem xuyên suốt facility → booking → finance → player → competition → integrations.
 */
class CommercialDemoSeeder extends Seeder
{
    private string $now;
    private int $tenantId = 0;
    private int $branchId = 0;
    private int $adminUserId = 0;
    private array $players = [];
    private array $courts = [];
    private array $teams = [];
    private int $clubId = 0;
    private int $tournamentMatchId = 0;

    public function run()
    {
        $this->now = date('Y-m-d H:i:s');

        if (! $this->db->tableExists('tenants')) {
            echo "Chưa có schema. Chạy php spark migrate trước.\n";
            return;
        }

        // Bootstrap các seeder nền khi chạy trên database mới.
        if ($this->db->table('tenants')->countAllResults() === 0) {
            $this->call('App\\Database\\Seeds\\CoreSeeder');
        }
        $this->call('App\\Database\\Seeds\\DemoDataSeeder');
        if ($this->db->table('tenant_plans')->countAllResults() === 0) {
            $this->call('App\\Database\\Seeds\\TenantPlanSeeder');
        }
        // Nạp quy mô dữ liệu người chơi đủ lớn cho dashboard, ranking và
        // các luồng matching/competition; seeder này đã được sửa prefix theo tenant.
        $this->call('App\\Database\\Seeds\\PlayerMembershipSeeder');
        if ($this->db->tableExists('notification_templates')) {
            $this->call('App\\Database\\Seeds\\NotificationTemplateSeeder');
        }
        $tenants = $this->db->table('tenants')
            ->where('is_active', 1)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
        if (empty($tenants)) {
            echo "Không có tenant active để tạo dữ liệu demo.\n";
            return;
        }

        $seeded = 0;
        foreach ($tenants as $tenant) {
            $this->resetContext((int) $tenant->id);
            $this->ensureTenantFoundation($tenant);
            $this->loadContext((int) $tenant->id);
            if (! $this->tenantId || ! $this->branchId || count($this->players) < 8 || ! $this->courts) {
                echo "Bỏ qua tenant {$tenant->id}: chưa đủ branch/player/court.\n";
                continue;
            }

            $this->db->transStart();
            $this->seedPlayerExperience();
            $this->seedTeamsAndSocial();
            $this->seedOpenPlay();
            $this->seedCoaching();
            $this->seedTournamentAndCompetition();
            $this->seedGrowth();
            $this->seedCommunity();
            $this->seedOperationsAndFinance();
            $this->seedIntegrations();
            $this->seedPlatformExpansion();
            $this->seedInternationalFoundation();
            $this->seedTenantDataPolicy();
            $this->seedVolumeData();
            $this->db->transComplete();

            if (! $this->db->transStatus()) {
                echo "CommercialDemoSeeder thất bại ở tenant {$this->tenantId}, transaction đã rollback.\n";
                continue;
            }
            $seeded++;
            echo "Đã tạo dữ liệu mẫu thương mại cho tenant {$tenant->code} (id {$this->tenantId}).\n";
        }

        // Bổ sung fixture cho 5 nền tảng trust/competition để bản demo có
        // đủ dữ liệu authority → ruleset → provenance → rating → match graph.
        if ($this->db->tableExists('governance_authorities')) {
            $this->call('App\\Database\\Seeds\\TrustCompetitionFoundationSeeder');
        }

        echo "Đã tạo dữ liệu mẫu thương mại đầy đủ cho {$seeded}/" . count($tenants) . " tenant active.\n";
        echo "Đăng nhập: admin@demo-pickleball.vn / admin123 hoặc player@demo-pickleball.vn / password\n";

        // Chuẩn hóa fixture giải đấu demo sau khi toàn bộ tenant đã có
        // player/team/court: đăng ký hợp lệ, lịch 7 trận và cây knockout.
        if ($this->db->tableExists('tournament_brackets')) {
            $this->call('App\\Database\\Seeds\\TournamentDemoIntegritySeeder');
            // Bổ sung cây đấu và participant labels cho các Demo Series,
            // đặc biệt để các nội dung single có dữ liệu hiển thị ngay.
            $this->call('App\\Database\\Seeds\\TournamentBracketSampleSeeder');
        }
        if ($this->db->tableExists('daily_closings') && $this->db->tableExists('crm_campaigns')) {
            $this->call('App\\Database\\Seeds\\CommercialOperationsSeeder');
        }
    }

    private function resetContext(int $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->branchId = 0;
        $this->adminUserId = 0;
        $this->players = [];
        $this->courts = [];
        $this->teams = [];
        $this->clubId = 0;
        $this->tournamentMatchId = 0;
    }

    private function ensureTenantFoundation(object $tenant): void
    {
        $tenantId = (int) $tenant->id;
        $existingBranch = $this->db->table('branches')
            ->where('tenant_id', $tenantId)
            ->where('is_main', 1)
            ->where('deleted_at', null)
            ->orderBy('id')
            ->get(1)
            ->getRow();
        $branchId = (int) ($existingBranch->id ?? 0);
        $facilityId = 0;
        if (! $branchId) {
            $branchCode = 'TENANT-' . $tenantId . '-MAIN';
            $branchId = $this->ensure('branches', ['tenant_id' => $tenantId, 'code' => $branchCode], [
                'tenant_id' => $tenantId,
                'code' => $branchCode,
                'name' => ($tenant->name ?? 'Pickleball') . ' - Cơ sở chính',
                'email' => 'branch' . $tenantId . '@demo-pickleball.vn',
                'phone' => '090100' . str_pad((string) $tenantId, 4, '0', STR_PAD_LEFT),
                'address' => $tenant->address ?? 'Địa chỉ demo',
                'city' => 'Hồ Chí Minh',
                'district' => 'Trung tâm',
                'is_main' => 1,
                'is_active' => 1,
                'status' => 'active',
            ]);
        }

        if ($this->table('facilities')) {
            $facilityId = $this->ensure('facilities', ['tenant_id' => $tenantId, 'code' => 'FACILITY-T' . $tenantId], [
                'tenant_id' => $tenantId,
                'code' => 'FACILITY-T' . $tenantId,
                'name_vi' => ($tenant->name ?? 'Pickleball') . ' - Cụm sân chính',
                'name_en' => ($tenant->name ?? 'Pickleball') . ' - Main Facility',
                'address' => $tenant->address ?? 'Địa chỉ demo',
                'city' => 'Hồ Chí Minh',
                'total_courts' => 6,
                'total_branches' => 1,
                'is_active' => 1,
                'status' => 'active',
            ]);
            if ($branchId && $facilityId) {
                $this->db->table('branches')->where('id', $branchId)->where('tenant_id', $tenantId)->update(['facility_id' => $facilityId, 'updated_at' => $this->now]);
            }
        }

        if ($this->table('court_types') && $this->table('courts')) {
            $courtType = $this->ensure('court_types', ['tenant_id' => $tenantId, 'name_vi' => 'Sân Standard Demo'], [
                'tenant_id' => $tenantId,
                'name_vi' => 'Sân Standard Demo',
                'name_en' => 'Demo Standard Court',
                'default_capacity' => 4,
                'status' => 'active',
            ]);
            for ($i = 1; $i <= 6; $i++) {
                $code = 'T' . $tenantId . '-C' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $this->ensure('courts', ['tenant_id' => $tenantId, 'code' => $code], [
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'court_type_id' => $courtType,
                    'code' => $code,
                    'name_vi' => 'Sân ' . $i,
                    'name_en' => 'Court ' . $i,
                    'floor' => 1,
                    'area' => 81.80,
                    'is_indoor' => 1,
                    'has_light' => 1,
                    'has_fan' => 1,
                    'has_camera' => 1,
                    'status' => 'available',
                    'sort_order' => $i,
                ]);
            }
        }
    }

    private function loadContext(int $tenantId): void
    {
        $this->tenantId = $tenantId;

        $branch = $this->db->table('branches')->where('tenant_id', $this->tenantId)->where('is_main', 1)->get()->getRow();
        $branch ??= $this->db->table('branches')->where('tenant_id', $this->tenantId)->orderBy('id')->get(1)->getRow();
        $this->branchId = (int) ($branch->id ?? 0);

        $user = $this->db->table('users')->where('tenant_id', $this->tenantId)->where('email', 'manager@demo-pickleball.vn')->get()->getRow();
        $user ??= $this->db->table('users')->where('tenant_id', $this->tenantId)->orderBy('id')->get(1)->getRow();
        $this->adminUserId = (int) ($user->id ?? 0);

        $planId = (int) ($this->db->table('tenant_plans')->where('code', 'pro')->get()->getRow('id') ?? 0);
        $hasSubscription = $this->db->table('tenant_subscriptions')
            ->where('tenant_id', $this->tenantId)
            ->whereIn('status', ['trial', 'active'])
            ->countAllResults() > 0;
        if ($planId && ! $hasSubscription) {
            (new TenantPlanService())->subscribe($this->tenantId, $planId, 'active');
        }

        $this->players = array_map('intval', array_column($this->db->table('players')->select('id')->where('tenant_id', $this->tenantId)->where('status', 'active')->orderBy('id')->get(20)->getResultArray(), 'id'));
        if (count($this->players) < 8) {
            $this->seedDemoPlayers();
            $this->players = array_map('intval', array_column($this->db->table('players')->select('id')->where('tenant_id', $this->tenantId)->where('status', 'active')->orderBy('id')->get(20)->getResultArray(), 'id'));
        }
        $this->courts = array_map('intval', array_column($this->db->table('courts')->select('id')->where('tenant_id', $this->tenantId)->where('branch_id', $this->branchId)->whereIn('status', ['available', 'occupied'])->orderBy('id')->get(20)->getResultArray(), 'id'));
    }

    private function seedDemoPlayers(): void
    {
        $levels = ['beginner', 'intermediate', 'advanced', 'pro'];
        for ($i = 1; $i <= 12; $i++) {
            $code = 'DEMO' . $this->tenantId . '-P' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $this->ensure('players', ['player_code' => $code], [
                'tenant_id' => $this->tenantId, 'player_code' => $code, 'full_name' => 'Demo Player T' . $this->tenantId . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'phone' => '0938' . str_pad((string) ($this->tenantId * 10000 + $i), 6, '0', STR_PAD_LEFT), 'email' => 'demo' . $this->tenantId . '.player' . $i . '@demo-pickleball.vn', 'gender' => $i % 2 ? 'male' : 'female', 'birthday' => date('Y-m-d', strtotime('-' . (24 + $i) . ' years')), 'region' => 'Hồ Chí Minh', 'home_branch_id' => $this->branchId, 'level' => $levels[$i % 4], 'rating_score' => 900 + ($i * 75), 'checkin_streak' => $i % 6, 'best_checkin_streak' => 5 + $i, 'mvp_count' => $i % 3, 'status' => 'active',
            ]);
        }
    }

    private function seedPlayerExperience(): void
    {
        $package = $this->ensure('membership_packages', ['tenant_id' => $this->tenantId, 'name_en' => 'Demo Premium'], [
            'tenant_id' => $this->tenantId, 'name_vi' => 'Hội viên Demo Premium', 'name_en' => 'Demo Premium', 'duration_days' => 365, 'price' => 4800000, 'discount_percent' => 15, 'booking_priority' => 2, 'status' => 'active', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        if ($this->table('player_levels')) {
            foreach ([
                ['code' => 'BEGINNER', 'name' => 'Beginner', 'min_rating' => 0, 'max_rating' => 999, 'color' => '#6c757d', 'sort_order' => 1],
                ['code' => 'INTERMEDIATE', 'name' => 'Intermediate', 'min_rating' => 1000, 'max_rating' => 1299, 'color' => '#0d6efd', 'sort_order' => 2],
                ['code' => 'ADVANCED', 'name' => 'Advanced', 'min_rating' => 1300, 'max_rating' => 1599, 'color' => '#fd7e14', 'sort_order' => 3],
                ['code' => 'PRO', 'name' => 'Pro', 'min_rating' => 1600, 'max_rating' => 2500, 'color' => '#dc3545', 'sort_order' => 4],
            ] as $level) {
                $this->ensure('player_levels', ['tenant_id' => $this->tenantId, 'code' => $level['code']], $level + ['tenant_id' => $this->tenantId, 'is_active' => 1]);
            }
        }

        foreach (array_slice($this->players, 0, 8) as $index => $playerId) {
            $this->ensure('memberships', ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'package_id' => $package], [
                'tenant_id' => $this->tenantId, 'player_id' => $playerId, 'package_id' => $package, 'start_date' => date('Y-m-d', strtotime('-10 days')), 'end_date' => date('Y-m-d', strtotime('+355 days')), 'status' => 'active', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
            ]);
            $wallet = $this->ensure('player_wallets', ['tenant_id' => $this->tenantId, 'player_id' => $playerId], ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'balance' => 750000]);
            $this->ensure('wallet_transactions', ['tenant_id' => $this->tenantId, 'wallet_id' => $wallet, 'ref_type' => 'commercial_demo', 'ref_id' => $index + 1], ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'wallet_id' => $wallet, 'type' => 'topup', 'amount' => 750000, 'balance_before' => 0, 'balance_after' => 750000, 'ref_type' => 'commercial_demo', 'ref_id' => $index + 1, 'note' => 'Nạp ví demo']);
            $this->ensure('player_statistics', ['tenant_id' => $this->tenantId, 'player_id' => $playerId], ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'elo_rating' => 1100 + ($index * 50), 'ranking_points' => 300 + ($index * 20), 'total_matches' => 12 + $index, 'total_wins' => 8 + $index, 'total_losses' => 4, 'total_bookings' => 6 + $index, 'checkin_count' => 10 + $index, 'win_rate' => 65.00, 'current_streak' => $index % 4, 'best_streak' => 6, 'mvp_count' => $index % 2, 'achievements_count' => 1]);
            $this->ensure('player_ratings', ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'scope_type' => 'global'], ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'scope_type' => 'global', 'rating_type' => 'elo', 'rating' => 1100 + ($index * 50), 'games_played' => 12 + $index, 'wins' => 8 + $index, 'losses' => 4, 'rank_position' => $index + 1, 'last_match_at' => date('Y-m-d H:i:s', strtotime('-2 days'))]);
            $this->ensure('player_achievements', ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'code' => 'FIRST_MATCH'], [
                'tenant_id' => $this->tenantId, 'player_id' => $playerId, 'code' => 'FIRST_MATCH', 'name' => 'Trận đấu đầu tiên', 'description' => 'Hoàn thành trận đấu đầu tiên tại câu lạc bộ.', 'points' => 50, 'achieved_at' => date('Y-m-d H:i:s', strtotime('-' . ($index + 1) . ' days')),
            ]);
            $this->ensure('player_badges', ['tenant_id' => $this->tenantId, 'player_id' => $playerId, 'badge_code' => 'COMMUNITY'], [
                'tenant_id' => $this->tenantId, 'player_id' => $playerId, 'badge_code' => 'COMMUNITY', 'name' => 'Người kết nối', 'description' => 'Tích cực tham gia cộng đồng.', 'rarity' => 'common', 'icon' => 'bi-people', 'source' => 'demo', 'earned_at' => $this->now,
            ]);
        }
    }

    private function seedTeamsAndSocial(): void
    {
        $clubId = $this->ensure('clubs', ['tenant_id' => $this->tenantId, 'name_vi' => 'CLB Demo Pickleball'], [
            'tenant_id' => $this->tenantId, 'name_vi' => 'CLB Demo Pickleball', 'name_en' => 'Demo Pickleball Club', 'description_vi' => 'Cộng đồng người chơi demo.', 'description_en' => 'Demo player community.', 'owner_player_id' => $this->players[0], 'status' => 'active',
        ]);
        $this->clubId = $clubId;
        $this->seedFacilityClubLinks();
        foreach ([['name' => 'Demo Smashers', 'captain' => 0, 'type' => 'mixed_double'], ['name' => 'Demo Dinks', 'captain' => 4, 'type' => 'group']] as $team) {
            $id = $this->ensure('teams', ['tenant_id' => $this->tenantId, 'team_name' => $team['name']], [
                'tenant_id' => $this->tenantId, 'club_id' => $clubId ?: null, 'team_name' => $team['name'], 'captain_player_id' => $this->players[$team['captain']], 'team_type' => $team['type'], 'rating_avg' => 1250, 'status' => 'active',
            ]);
            $this->teams[] = $id;
        }
        foreach ($this->teams as $teamIndex => $teamId) {
            $desiredPlayers = array_slice($this->players, $teamIndex * 4, 4);
            if ($desiredPlayers) {
                $this->db->table('teams')->where('id', $teamId)->where('tenant_id', $this->tenantId)->update(['captain_player_id' => $desiredPlayers[0], 'updated_at' => $this->now]);
                $this->db->table('team_members')->where('team_id', $teamId)->where('tenant_id', $this->tenantId)->whereNotIn('player_id', $desiredPlayers)->update(['status' => 'removed', 'deleted_at' => $this->now, 'updated_at' => $this->now]);
            }
            foreach ($desiredPlayers as $index => $playerId) {
                $this->ensure('team_members', ['tenant_id' => $this->tenantId, 'team_id' => $teamId, 'player_id' => $playerId], [
                    'tenant_id' => $this->tenantId, 'team_id' => $teamId, 'player_id' => $playerId, 'role' => $index === 0 ? 'captain' : 'member', 'status' => 'accepted',
                ]);
            }
        }
        $this->ensure('player_follows', ['tenant_id' => $this->tenantId, 'follower_player_id' => $this->players[0], 'following_player_id' => $this->players[1]], [
            'tenant_id' => $this->tenantId, 'follower_player_id' => $this->players[0], 'following_player_id' => $this->players[1], 'status' => 'active',
        ]);
        $this->ensure('player_favorites', ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'entity_type' => 'club', 'entity_id' => $clubId], [
            'tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'entity_type' => 'club', 'entity_id' => $clubId,
        ]);
        $matchRequest = $this->ensure('match_requests', ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'preferred_date' => date('Y-m-d', strtotime('+3 days'))], [
            'tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'branch_id' => $this->branchId, 'preferred_date' => date('Y-m-d', strtotime('+3 days')), 'preferred_start_time' => '18:00:00', 'preferred_end_time' => '20:00:00', 'level_from' => 900, 'level_to' => 1800, 'match_type' => 'double', 'need_players' => 2, 'status' => 'open',
        ]);
        $socialMatch = $this->ensure('social_matches', ['tenant_id' => $this->tenantId, 'match_request_id' => $matchRequest], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'match_request_id' => $matchRequest, 'match_date' => date('Y-m-d', strtotime('+3 days')), 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'status' => 'pending',
        ]);
        foreach (array_slice($this->players, 0, 4) as $index => $playerId) {
            $this->ensure('social_match_players', ['tenant_id' => $this->tenantId, 'social_match_id' => $socialMatch, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'social_match_id' => $socialMatch, 'player_id' => $playerId, 'team_side' => $index < 2 ? 'A' : 'B', 'status' => 'confirmed',
            ]);
        }
    }

    private function seedFacilityClubLinks(): void
    {
        if (! $this->clubId || ! $this->db->tableExists('facility_club_assignments')) return;

        $branch = $this->db->table('branches')->where('id', $this->branchId)->where('tenant_id', $this->tenantId)->get()->getRow();
        $facilityId = (int) ($branch->facility_id ?? 0);
        if ($facilityId) {
            $this->ensure('facility_club_assignments', ['tenant_id' => $this->tenantId, 'facility_id' => $facilityId, 'club_id' => $this->clubId], [
                'tenant_id' => $this->tenantId, 'facility_id' => $facilityId, 'club_id' => $this->clubId, 'status' => 'active', 'is_primary' => 1, 'notes' => 'CLB demo vận hành cụm sân chính', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
            ]);
        }
        if ($this->db->fieldExists('club_id', 'courts')) {
            $this->db->table('courts')->where('tenant_id', $this->tenantId)->where('branch_id', $this->branchId)->where('deleted_at', null)->update(['club_id' => $this->clubId, 'updated_at' => $this->now]);
        }
    }

    private function seedOpenPlay(): void
    {
        $session = $this->ensure('open_play_sessions', ['tenant_id' => $this->tenantId, 'title' => 'Open Play Demo - tối thứ Sáu'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'host_player_id' => $this->players[0], 'title' => 'Open Play Demo - tối thứ Sáu', 'session_date' => date('Y-m-d', strtotime('+4 days')), 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'capacity' => 8, 'min_level' => 'beginner', 'max_level' => 'advanced', 'price_per_player' => 120000, 'visibility' => 'public', 'status' => 'open', 'notes' => 'Mang vợt cá nhân hoặc đăng ký thuê tại quầy.', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        foreach (array_slice($this->players, 0, 4) as $index => $playerId) {
            $this->ensure('open_play_session_players', ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $playerId, 'status' => $index === 3 ? 'requested' : 'approved', 'requested_at' => $this->now, 'approved_at' => $index === 3 ? null : $this->now, 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
            ]);
        }
        $round = $this->ensure('open_play_rotation_rounds', ['tenant_id' => $this->tenantId, 'session_id' => $session, 'round_no' => 1], [
            'tenant_id' => $this->tenantId, 'session_id' => $session, 'round_no' => 1, 'start_time' => '18:00:00', 'end_time' => '18:20:00', 'status' => 'planned', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        foreach (array_slice($this->players, 0, 4) as $index => $playerId) {
            $this->ensure('open_play_rotation_players', ['tenant_id' => $this->tenantId, 'round_id' => $round, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'round_id' => $round, 'player_id' => $playerId, 'team_side' => $index < 2 ? 'A' : 'B', 'partner_player_id' => $this->players[$index % 2 === 0 ? $index + 1 : $index - 1], 'opponent_player_ids' => json_encode(array_slice($this->players, 2, 2)),
            ]);
        }
        $this->ensure('player_favorites', ['tenant_id' => $this->tenantId, 'player_id' => $this->players[1], 'entity_type' => 'open_play', 'entity_id' => $session], [
            'tenant_id' => $this->tenantId, 'player_id' => $this->players[1], 'entity_type' => 'open_play', 'entity_id' => $session,
        ]);
    }

    private function seedCoaching(): void
    {
        $coach = $this->ensure('coaches', ['tenant_id' => $this->tenantId, 'email' => 'coach.demo@demo-pickleball.vn'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'user_id' => null, 'full_name' => 'Nguyễn Minh Coach', 'phone' => '0903555001', 'email' => 'coach.demo@demo-pickleball.vn', 'bio' => 'HLV pickleball chuyên beginner và doubles.', 'certifications' => json_encode(['IPTP Level 1']), 'specialties' => json_encode(['doubles', 'footwork', 'serve']), 'hourly_rate' => 450000, 'status' => 'active', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        $this->ensure('coach_availabilities', ['tenant_id' => $this->tenantId, 'coach_id' => $coach, 'day_of_week' => 5, 'start_time' => '17:00:00', 'end_time' => '21:00:00'], [
            'tenant_id' => $this->tenantId, 'coach_id' => $coach, 'branch_id' => $this->branchId, 'day_of_week' => 5, 'start_time' => '17:00:00', 'end_time' => '21:00:00', 'status' => 'active',
        ]);
        $this->ensure('coach_blackouts', ['tenant_id' => $this->tenantId, 'coach_id' => $coach, 'start_at' => date('Y-m-d 12:00:00', strtotime('+10 days'))], [
            'tenant_id' => $this->tenantId, 'coach_id' => $coach, 'start_at' => date('Y-m-d 12:00:00', strtotime('+10 days')), 'end_at' => date('Y-m-d 14:00:00', strtotime('+10 days')), 'reason' => 'HLV nghỉ cá nhân', 'status' => 'active', 'created_by' => $this->adminUserId,
        ]);
        $session = $this->ensure('coaching_sessions', ['tenant_id' => $this->tenantId, 'title' => 'Clinic kỹ thuật giao bóng Demo'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'coach_id' => $coach, 'court_id' => $this->courts[0], 'title' => 'Clinic kỹ thuật giao bóng Demo', 'session_type' => 'clinic', 'session_date' => date('Y-m-d', strtotime('+5 days')), 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'capacity' => 6, 'price_per_player' => 350000, 'status' => 'open', 'notes' => 'Clinic mẫu cho player portal.', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        foreach (array_slice($this->players, 0, 3) as $index => $playerId) {
            $entry = $this->ensure('coaching_session_players', ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $playerId, 'status' => 'approved', 'requested_at' => $this->now, 'approved_at' => $this->now, 'created_by' => $this->adminUserId,
            ]);
            $this->ensure('coaching_attendance', ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $playerId, 'status' => $index === 0 ? 'attended' : 'registered', 'checkin_at' => $index === 0 ? $this->now : null, 'note' => 'Dữ liệu demo',
            ]);
        }
    }

    private function seedTournamentAndCompetition(): void
    {
        $tournament = $this->ensure('tournaments', ['tenant_id' => $this->tenantId, 'slug_vi' => 'demo-summer-cup'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'name_vi' => 'Demo Summer Cup 2026', 'name_en' => 'Demo Summer Cup 2026', 'slug_vi' => 'demo-summer-cup', 'slug_en' => 'demo-summer-cup', 'description_vi' => 'Giải đấu mẫu cho toàn bộ luồng đăng ký, lịch và điểm.', 'description_en' => 'Demo tournament for registration, scheduling and scoring.', 'start_date' => date('Y-m-d', strtotime('+7 days')), 'end_date' => date('Y-m-d', strtotime('+8 days')), 'registration_start' => date('Y-m-d H:i:s', strtotime('-7 days')), 'registration_end' => date('Y-m-d H:i:s', strtotime('+3 days')), 'max_teams' => 16, 'registration_fee' => 250000, 'status' => 'running', 'created_at' => $this->now, 'updated_at' => $this->now,
        ]);
        $category = $this->ensure('tournament_categories', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'name_vi' => 'Đôi nam nữ phong trào'], [
            'tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'name_vi' => 'Đôi nam nữ phong trào', 'name_en' => 'Mixed Doubles Open', 'category_type' => 'mixed_double', 'max_teams' => 16, 'min_rating' => 800, 'max_rating' => 1800, 'registration_fee' => 250000, 'status' => 'active',
        ]);
        $registrations = [];
        foreach (array_slice($this->players, 0, 4) as $index => $playerId) {
            $registrations[] = $this->ensure('tournament_registrations', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'category_id' => $category, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'category_id' => $category, 'player_id' => $playerId, 'contact_name' => 'Demo Player ' . ($index + 1), 'contact_phone' => '09030000' . ($index + 1), 'payment_status' => $index === 0 ? 'paid' : 'unpaid', 'approval_status' => 'approved', 'invoice_code' => 'INV-DEMO-TOUR-' . ($index + 1), 'invoice_amount' => 250000,
            ]);
        }
        $this->ensure('tournament_rules', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament], ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'rule_content_vi' => 'Thi đấu chạm 11, cách 2, tối đa 15.', 'rule_content_en' => 'Games to 11, win by 2, cap at 15.']);
        $this->ensure('tournament_sponsors', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'sponsor_name' => 'Demo Sports'], ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'sponsor_name' => 'Demo Sports', 'website' => 'https://example.com', 'sort_order' => 1, 'status' => 'active']);
        $match = $this->ensure('tournament_matches', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'category_id' => $category, 'match_no' => 1], [
            'tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'category_id' => $category, 'round_name' => 'Bán kết', 'match_no' => 1, 'court_id' => $this->courts[1] ?? $this->courts[0], 'scheduled_date' => date('Y-m-d', strtotime('+7 days')), 'start_time' => '08:00:00', 'end_time' => '08:45:00', 'team_a_id' => $this->teams[0] ?? null, 'team_b_id' => $this->teams[1] ?? null, 'status' => 'scheduled', 'is_locked' => 0,
        ]);
        $this->tournamentMatchId = $match;
        $this->ensure('tournament_match_scores', ['tenant_id' => $this->tenantId, 'match_id' => $match, 'set_no' => 1], ['tenant_id' => $this->tenantId, 'match_id' => $match, 'set_no' => 1, 'team_a_score' => 11, 'team_b_score' => 8, 'winner_team_id' => $this->teams[0] ?? null]);
        $this->ensure('tournament_score_logs', ['tenant_id' => $this->tenantId, 'match_id' => $match], ['tenant_id' => $this->tenantId, 'match_id' => $match, 'old_score_json' => json_encode(['a' => 0, 'b' => 0]), 'new_score_json' => json_encode(['a' => 11, 'b' => 8]), 'changed_by' => $this->adminUserId, 'reason' => 'Cập nhật điểm mẫu']);
        $event = $this->ensure('competition_events', ['tenant_id' => $this->tenantId, 'name' => 'Demo League 2026'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'tournament_id' => $tournament, 'name' => 'Demo League 2026', 'format' => 'round_robin', 'entry_fee' => 180000, 'scoring_rules' => json_encode(['win' => 3, 'draw' => 1]), 'start_date' => date('Y-m-d', strtotime('+10 days')), 'end_date' => date('Y-m-d', strtotime('+12 days')), 'status' => 'running', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        $participants = [];
        foreach (array_slice($this->players, 0, 4) as $index => $playerId) {
            $participants[] = $this->ensure('competition_participants', ['tenant_id' => $this->tenantId, 'event_id' => $event, 'player_id' => $playerId], [
                'tenant_id' => $this->tenantId, 'event_id' => $event, 'player_id' => $playerId, 'display_name' => 'Demo Player ' . ($index + 1), 'seed' => $index + 1, 'status' => 'active',
            ]);
        }
        $fixture = $this->ensure('competition_fixtures', ['tenant_id' => $this->tenantId, 'event_id' => $event, 'round_no' => 1, 'match_no' => 1], [
            'tenant_id' => $this->tenantId, 'event_id' => $event, 'round_no' => 1, 'match_no' => 1, 'participant_a_id' => $participants[0], 'participant_b_id' => $participants[1], 'scheduled_date' => date('Y-m-d', strtotime('+10 days')), 'start_time' => '09:00:00', 'court_id' => $this->courts[0], 'score_a' => 11, 'score_b' => 7, 'winner_id' => $participants[0], 'status' => 'completed',
        ]);
        foreach ($participants as $index => $participant) {
            $this->ensure('competition_standings', ['tenant_id' => $this->tenantId, 'event_id' => $event, 'participant_id' => $participant], [
                'tenant_id' => $this->tenantId, 'event_id' => $event, 'participant_id' => $participant, 'played' => $index < 2 ? 1 : 0, 'wins' => $index === 0 ? 1 : 0, 'losses' => $index === 1 ? 1 : 0, 'points_for' => $index === 0 ? 11 : ($index === 1 ? 7 : 0), 'points_against' => $index === 0 ? 7 : ($index === 1 ? 11 : 0), 'points' => $index === 0 ? 3 : 0, 'rank_no' => $index + 1,
            ]);
            $this->ensure('competition_checkins', ['tenant_id' => $this->tenantId, 'event_id' => $event, 'participant_id' => $participant], ['tenant_id' => $this->tenantId, 'event_id' => $event, 'participant_id' => $participant, 'status' => $index < 2 ? 'checked_in' : 'pending', 'checkin_at' => $index < 2 ? $this->now : null]);
        }
        $this->ensure('competition_ladder_challenges', ['tenant_id' => $this->tenantId, 'event_id' => $event, 'challenger_id' => $participants[2], 'opponent_id' => $participants[0], 'status' => 'requested'], [
            'tenant_id' => $this->tenantId, 'event_id' => $event, 'challenger_id' => $participants[2], 'opponent_id' => $participants[0], 'fixture_id' => $fixture, 'scheduled_date' => date('Y-m-d', strtotime('+11 days')), 'start_time' => '18:00:00', 'status' => 'requested', 'expires_at' => date('Y-m-d H:i:s', strtotime('+5 days')), 'created_by' => $this->adminUserId,
        ]);
    }

    private function seedGrowth(): void
    {
        $promotion = $this->ensure('promotions', ['tenant_id' => $this->tenantId, 'code' => 'DEMO20'], [
            'tenant_id' => $this->tenantId, 'code' => 'DEMO20', 'name' => 'Ưu đãi demo 20%', 'discount_type' => 'percent', 'discount_value' => 20, 'max_discount' => 100000, 'min_order_amount' => 100000, 'usage_limit' => 100, 'per_customer_limit' => 2, 'starts_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')), 'status' => 'active', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        $this->ensure('promotion_redemptions', ['tenant_id' => $this->tenantId, 'promotion_id' => $promotion, 'idempotency_key' => 'demo-redemption-001'], [
            'tenant_id' => $this->tenantId, 'promotion_id' => $promotion, 'player_id' => $this->players[0], 'discount_amount' => 50000, 'idempotency_key' => 'demo-redemption-001',
        ]);
        $this->ensure('referral_codes', ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0]], ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'code' => 'DEMOREF001', 'reward_amount' => 100000, 'uses_count' => 1, 'max_uses' => 20, 'status' => 'active']);
        $this->ensure('referrals', ['tenant_id' => $this->tenantId, 'referred_player_id' => $this->players[1]], ['tenant_id' => $this->tenantId, 'referrer_player_id' => $this->players[0], 'referred_player_id' => $this->players[1], 'code' => 'DEMOREF001', 'reward_amount' => 100000, 'status' => 'qualified', 'qualified_at' => $this->now]);
        $this->ensure('reviews', ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'entity_type' => 'booking', 'entity_id' => $this->firstBookingId()], ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'entity_type' => 'booking', 'entity_id' => $this->firstBookingId(), 'rating' => 5, 'title' => 'Sân rất tốt', 'body' => 'Mặt sân sạch, ánh sáng tốt.', 'status' => 'published']);
    }

    private function seedCommunity(): void
    {
        $post = $this->ensure('community_posts', ['tenant_id' => $this->tenantId, 'title' => 'Mẹo giao bóng ổn định'], ['tenant_id' => $this->tenantId, 'player_id' => $this->players[0], 'type' => 'tip', 'title' => 'Mẹo giao bóng ổn định', 'body' => 'Giữ nhịp chân ổn định, đánh cao điểm tiếp xúc và ưu tiên độ sâu.', 'status' => 'published']);
        $this->ensure('community_comments', ['tenant_id' => $this->tenantId, 'post_id' => $post, 'player_id' => $this->players[1]], ['tenant_id' => $this->tenantId, 'post_id' => $post, 'player_id' => $this->players[1], 'body' => 'Mẹo rất hữu ích, cảm ơn bạn!', 'status' => 'published']);
        $this->ensure('community_reactions', ['tenant_id' => $this->tenantId, 'post_id' => $post, 'player_id' => $this->players[2]], ['tenant_id' => $this->tenantId, 'post_id' => $post, 'player_id' => $this->players[2], 'reaction' => 'like']);
    }

    private function seedOperationsAndFinance(): void
    {
        $bookingId = $this->firstBookingId();
        $this->ensure('booking_recurring_templates', ['tenant_id' => $this->tenantId, 'name' => 'Demo cố định thứ Sáu'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'court_id' => $this->courts[0], 'player_id' => $this->players[0], 'name' => 'Demo cố định thứ Sáu', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+60 days')), 'start_time' => '19:00:00', 'end_time' => '20:00:00', 'duration_minutes' => 60, 'repeat_type' => 'weekly', 'repeat_interval' => 1, 'repeat_days' => json_encode([5]), 'exclude_dates' => json_encode([]), 'status' => 'active', 'total_occurrences' => 8, 'completed_occurrences' => 2, 'next_occurrence' => date('Y-m-d', strtotime('+5 days')), 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        $this->ensure('booking_waitlist', ['tenant_id' => $this->tenantId, 'idempotency_key' => 'demo-waitlist-001'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'court_id' => $this->courts[0], 'player_id' => $this->players[3], 'customer_name' => 'Demo Player 4', 'customer_phone' => '0903000004', 'customer_email' => 'player4@demo-pickleball.vn', 'booking_date' => date('Y-m-d', strtotime('+2 days')), 'start_time' => '18:00:00', 'end_time' => '19:00:00', 'duration_minutes' => 60, 'priority' => 100, 'status' => 'waiting', 'idempotency_key' => 'demo-waitlist-001', 'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days')), 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        if ($bookingId) {
            $this->ensure('walk_in_sessions', ['tenant_id' => $this->tenantId, 'session_key' => 'demo-walkin-001'], [
                'tenant_id' => $this->tenantId, 'booking_id' => $bookingId, 'branch_id' => $this->branchId, 'player_id' => $this->players[1], 'customer_name' => 'Demo Player 2', 'customer_phone' => '0903000002', 'customer_email' => 'player2@demo-pickleball.vn', 'session_key' => 'demo-walkin-001', 'status' => 'open', 'note' => 'Walk-in demo', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
            ]);
        }
        $invoice = $this->ensure('invoices', ['tenant_id' => $this->tenantId, 'invoice_code' => 'INV-DEMO-BOOKING-001'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'invoice_code' => 'INV-DEMO-BOOKING-001', 'customer_type' => 'player', 'player_id' => $this->players[0], 'ref_type' => 'booking', 'ref_id' => $bookingId ?: null, 'subtotal' => 320000, 'discount_amount' => 0, 'total_amount' => 320000, 'paid_amount' => 320000, 'status' => 'paid', 'note' => 'Hóa đơn booking mẫu', 'created_by' => $this->adminUserId,
        ]);
        $payment = $this->ensure('payments', ['tenant_id' => $this->tenantId, 'payment_code' => 'PAY-DEMO-001'], [
            'tenant_id' => $this->tenantId, 'invoice_id' => $invoice, 'payment_code' => 'PAY-DEMO-001', 'method' => 'cash', 'amount' => 320000, 'transaction_ref' => $this->tenantKey('CASH-DEMO-001'), 'status' => 'success', 'idempotency_key' => $this->tenantKey('demo-payment-001'), 'paid_at' => $this->now, 'created_by' => $this->adminUserId,
        ]);
        $this->db->table('payments')->where('tenant_id', $this->tenantId)->where('payment_code', 'PAY-DEMO-001')->update(['status' => 'success', 'updated_at' => $this->now]);
        $this->ensure('refunds', ['tenant_id' => $this->tenantId, 'payment_id' => $payment], ['tenant_id' => $this->tenantId, 'payment_id' => $payment, 'invoice_id' => $invoice, 'amount' => 50000, 'reason' => 'Hoàn phí mẫu một phần', 'status' => 'approved', 'processed_by' => $this->adminUserId]);
        $category = $this->ensure('product_categories', ['tenant_id' => $this->tenantId, 'name_vi' => 'Đồ uống Demo'], ['tenant_id' => $this->tenantId, 'name_vi' => 'Đồ uống Demo', 'name_en' => 'Demo Beverages', 'status' => 'active']);
        $product = $this->ensure('products', ['tenant_id' => $this->tenantId, 'sku' => 'DEMO-WATER-001'], ['tenant_id' => $this->tenantId, 'category_id' => $category, 'sku' => 'DEMO-WATER-001', 'name_vi' => 'Nước suối Demo', 'name_en' => 'Demo Mineral Water', 'unit' => 'chai', 'cost_price' => 3000, 'sale_price' => 10000, 'status' => 'active']);
        $this->ensure('inventories', ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'product_id' => $product], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'product_id' => $product, 'quantity' => 120]);
        $this->ensure('inventory_movements', ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'product_id' => $product, 'ref_type' => 'demo', 'ref_id' => 1], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'product_id' => $product, 'movement_type' => 'import', 'quantity' => 120, 'before_qty' => 0, 'after_qty' => 120, 'ref_type' => 'demo', 'ref_id' => 1, 'note' => 'Nhập kho mẫu', 'created_by' => $this->adminUserId]);
        $order = $this->ensure('pos_orders', ['tenant_id' => $this->tenantId, 'order_code' => 'POS-DEMO-001'], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'player_id' => $this->players[0], 'booking_id' => $bookingId ?: null, 'order_code' => 'POS-DEMO-001', 'total_amount' => 20000, 'discount_amount' => 0, 'paid_amount' => 20000, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'POS demo', 'created_by' => $this->adminUserId]);
        $this->ensure('pos_order_items', ['tenant_id' => $this->tenantId, 'order_id' => $order, 'product_id' => $product], ['tenant_id' => $this->tenantId, 'order_id' => $order, 'product_id' => $product, 'quantity' => 2, 'price' => 10000, 'total' => 20000]);
        $this->ensure('notifications', ['tenant_id' => $this->tenantId, 'user_id' => $this->adminUserId, 'template_code' => 'demo.booking.created'], ['tenant_id' => $this->tenantId, 'user_id' => $this->adminUserId, 'template_code' => 'demo.booking.created', 'title' => 'Booking mới', 'message' => 'Bạn có booking demo cần theo dõi.', 'channel' => 'in_app', 'data' => json_encode(['booking_id' => $bookingId]), 'is_read' => 0, 'action_url' => '/admin/bookings']);
    }

    private function seedIntegrations(): void
    {
        if ($this->table('platform_integrations') && $this->table('tenant_integrations')) {
            foreach (['internal-webhooks', 'partner-api', 'external-rating'] as $code) {
                $integration = $this->db->table('platform_integrations')->where('code', $code)->get()->getRow();
                if ($integration) {
                    $this->ensure('tenant_integrations', ['tenant_id' => $this->tenantId, 'integration_id' => $integration->id], [
                        'tenant_id' => $this->tenantId, 'integration_id' => $integration->id, 'status' => 'active',
                        'metadata' => json_encode(['source' => 'commercial_demo', 'health' => 'ok']),
                    ]);
                }
            }
        }
        if ($this->table('partner_api_keys')) {
            $demoKey = 'pk_live_demo_t' . $this->tenantId . '_partner_2026';
            $this->ensure('partner_api_keys', ['key_hash' => hash('sha256', $demoKey)], [
                'tenant_id' => $this->tenantId, 'name' => 'Demo Partner App', 'key_prefix' => substr($demoKey, 0, 16),
                'key_hash' => hash('sha256', $demoKey), 'scopes' => json_encode(['players.read', 'rankings.read', 'clubs.read', 'tournaments.read']),
                'status' => 'active', 'created_by' => $this->adminUserId,
            ]);
        }
        $tournament = $this->firstId('tournaments', ['tenant_id' => $this->tenantId, 'slug_vi' => 'demo-summer-cup']);
        $this->ensure('ai_scheduling_requests', ['tenant_id' => $this->tenantId, 'date_from' => date('Y-m-d', strtotime('+7 days')), 'date_to' => date('Y-m-d', strtotime('+8 days'))], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'tournament_id' => $tournament ?: null, 'requested_by' => $this->adminUserId, 'date_from' => date('Y-m-d', strtotime('+7 days')), 'date_to' => date('Y-m-d', strtotime('+8 days')), 'match_minutes' => 60, 'rest_minutes' => 30, 'provider' => 'local', 'constraints_json' => json_encode(['max_matches_per_day' => 8, 'respect_rest' => true]), 'status' => 'completed', 'result_json' => json_encode(['engine' => 'local_heuristic', 'provider_requested' => 'local', 'court_count' => count($this->courts), 'match_count' => 1, 'suggestions' => []]),
        ]);
        $this->ensure('livestream_channels', ['tenant_id' => $this->tenantId, 'name' => 'Demo Court 1 Live'], [
            'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'tournament_id' => $tournament ?: null, 'name' => 'Demo Court 1 Live', 'provider' => 'youtube', 'stream_url' => 'https://www.youtube.com/watch?v=demo-pickleball', 'embed_url' => 'https://www.youtube.com/embed/demo-pickleball', 'status' => 'scheduled', 'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 day 18:00')), 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
        $secret = 'demo-webhook-secret-2026';
        $this->ensure('webhook_endpoints', ['tenant_id' => $this->tenantId, 'name' => 'Demo Partner ERP'], [
            'tenant_id' => $this->tenantId, 'name' => 'Demo Partner ERP', 'url' => 'https://example.com/pickleball/webhook', 'secret_ciphertext' => WebhookService::encryptSecret($secret), 'event_types' => json_encode(['booking.created', 'payment.succeeded', 'match.official', 'ranking.updated']), 'status' => 'active', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
        ]);
    }

    /**
     * Dữ liệu demo cho các phase Platform mới: passport, club registry,
     * unified match, official result, rating/ranking ledger và dispute.
     * Mọi khóa tìm kiếm đều ổn định để chạy seeder nhiều lần không nhân bản.
     */
    private function seedPlatformExpansion(): void
    {
        if ($this->table('player_competitive_profiles')) {
            foreach (array_slice($this->players, 0, 12) as $index => $playerId) {
                // National ID và slug là unique toàn hệ thống; dùng player id
                // để các tenant khác nhau không đụng dữ liệu khi seed chung DB.
                $nationalId = 'VNP-' . str_pad((string) $playerId, 11, '0', STR_PAD_LEFT);
                $this->ensure('player_competitive_profiles', ['player_id' => $playerId], [
                    'player_id' => $playerId,
                    'national_player_id' => $nationalId,
                    'display_name' => 'Demo Player #' . $playerId,
                    'country_code' => 'VN',
                    'administrative_area_code' => 'VN-SG',
                    'slug' => 'demo-player-' . $playerId,
                    'province_id' => null,
                    'city_id' => null,
                    'club_id' => $this->clubId ?: null,
                    'gender_category' => $index % 2 ? 'women' : 'men',
                    'age_category_public' => 'open',
                    'reliability_score' => min(100, 40 + $index * 4),
                    'privacy_level' => 'public',
                    'status' => 'verified',
                    'verified_at' => date('Y-m-d H:i:s', strtotime('-' . ($index + 1) . ' days')),
                ]);
                $player = $this->db->table('players')->where('id', $playerId)->get()->getRow();
                foreach ([['type' => 'phone', 'value' => (string) ($player->phone ?? '093800' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT))], ['type' => 'email', 'value' => (string) ($player->email ?? 'demo.player' . ($index + 1) . '@demo-pickleball.vn')]] as $claim) {
                    $this->ensure('player_identity_claims', ['player_id' => $playerId, 'claim_type' => $claim['type'], 'claim_value' => $claim['value']], [
                        'player_id' => $playerId, 'claim_type' => $claim['type'], 'claim_value' => $claim['value'], 'verified_at' => $this->now, 'verification_source' => 'commercial_demo', 'is_primary' => 1,
                    ]);
                }
                if ($this->clubId) {
                    $this->ensure('player_club_memberships', ['tenant_id' => $this->tenantId, 'club_id' => $this->clubId, 'player_id' => $playerId], [
                        'tenant_id' => $this->tenantId, 'club_id' => $this->clubId, 'player_id' => $playerId, 'role' => $index === 0 ? 'owner' : 'member', 'status' => 'active', 'source' => 'commercial_demo', 'is_primary' => $index === 0 ? 1 : 0, 'joined_at' => date('Y-m-d H:i:s', strtotime('-30 days')), 'verified_at' => $this->now, 'verified_by' => $this->adminUserId,
                    ]);
                }
            }
            $this->ensure('player_identity_candidates', ['player_id' => $this->players[0], 'candidate_player_id' => $this->players[1]], [
                'player_id' => $this->players[0], 'candidate_player_id' => $this->players[1], 'match_type' => 'demo_review', 'confidence_score' => 42.50, 'status' => 'open', 'evidence' => json_encode(['source' => 'commercial_demo', 'note' => 'Ứng viên trùng cần kiểm tra thủ công']),
            ]);
        }

        $platformClub = 0;
        if ($this->table('platform_clubs')) {
            $platformClub = $this->ensure('platform_clubs', ['slug' => 'demo-pickleball-club-national'], [
                'public_id' => '550e8400-e29b-41d4-a716-446655440001', 'code' => 'DEMO-NETWORK', 'name' => 'Demo Pickleball Club', 'slug' => 'demo-pickleball-club-national', 'province' => 'Hồ Chí Minh', 'city' => 'Hồ Chí Minh', 'status' => 'active', 'verification_status' => 'official', 'metadata' => json_encode(['source' => 'commercial_demo', 'branches' => 1]),
            ]);
            if ($platformClub && $this->clubId) {
                $this->ensure('platform_club_aliases', ['tenant_id' => $this->tenantId, 'club_id' => $this->clubId], [
                    'platform_club_id' => $platformClub, 'tenant_id' => $this->tenantId, 'club_id' => $this->clubId, 'status' => 'verified', 'linked_by' => $this->adminUserId, 'verified_at' => $this->now,
                ]);
            }
        }

        if ($this->tournamentMatchId && $this->table('tournament_matches')) {
            $this->db->table('tournament_matches')->where('id', $this->tournamentMatchId)->update([
                'winner_team_id' => $this->teams[0] ?? null,
                'status' => 'completed',
                'updated_at' => $this->now,
            ]);
            try {
                $published = service('tournamentMatchNetworkAdapter')->publishOfficial($this->tournamentMatchId, $this->tenantId, $this->adminUserId);
                $unifiedId = (int) ($published['match']['match']->id ?? 0);
                if ($unifiedId && $this->table('match_disputes')) {
                    service('matchGovernanceService')->open($unifiedId, $this->tenantId, $this->adminUserId, [
                        'reason_code' => 'demo_result_challenge',
                        'reason' => 'Demo dispute để kiểm thử màn hình governance và quy trình khiếu nại.',
                        'evidence' => ['score_sheet' => 'demo-score-sheet-001', 'source' => 'commercial_demo'],
                    ]);
                }
                if ($this->table('ranking_snapshots')) {
                    service('rankingNetworkService')->createSnapshot(date('Y-m-d'), 'national-pickleball', $this->tenantId);
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException('Không thể tạo dữ liệu unified match/rating/ranking demo: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    /** Country context, organization memberships, immutable ruleset snapshot and provenance demo. */
    private function seedInternationalFoundation(): void
    {
        if ($this->table('tenants')) {
            $this->db->table('tenants')->where('id', $this->tenantId)->update([
                'country_code' => 'VN', 'default_timezone' => 'Asia/Ho_Chi_Minh', 'default_currency' => 'VND', 'default_locale' => 'vi-VN', 'updated_at' => $this->now,
            ]);
        }
        if ($this->table('branches')) {
            $this->db->table('branches')->where('id', $this->branchId)->where('tenant_id', $this->tenantId)->update([
                'country_code' => 'VN', 'timezone' => 'Asia/Ho_Chi_Minh', 'currency' => 'VND', 'updated_at' => $this->now,
            ]);
        }
        if ($this->table('organization_memberships') && $this->adminUserId) {
            foreach (['owner', 'organizer', 'branch-manager'] as $index => $role) {
                $this->ensure('organization_memberships', ['tenant_id' => $this->tenantId, 'user_id' => $this->adminUserId, 'role_code' => $role], [
                    'tenant_id' => $this->tenantId, 'user_id' => $this->adminUserId, 'branch_id' => $index === 2 ? $this->branchId : null, 'role_code' => $role, 'status' => 'active', 'is_primary' => $index === 0 ? 1 : 0, 'starts_at' => date('Y-m-d H:i:s', strtotime('-90 days')), 'metadata' => json_encode(['source' => 'commercial_demo']),
                ]);
            }
        }
        if (! $this->table('competition_rulesets') || ! $this->table('competition_ruleset_versions')) return;
        $ruleset = $this->db->table('competition_rulesets')->where('code', 'pickleball-standard')->get()->getRow();
        $version = $ruleset ? $this->db->table('competition_ruleset_versions')->where('ruleset_id', $ruleset->id)->where('version', '1.0')->get()->getRow() : null;
        if (! $ruleset || ! $version) return;
        foreach ($this->db->table('tournaments')->where('tenant_id', $this->tenantId)->where('deleted_at', null)->orderBy('id')->limit(10)->get()->getResult() as $tournament) {
            $this->db->table('tournaments')->where('id', $tournament->id)->update(['ruleset_id' => $ruleset->id, 'ruleset_version_id' => $version->id, 'tier_code' => $tournament->verification_level === 'official' ? 'national' : 'club', 'country_code' => 'VN', 'updated_at' => $this->now]);
            if ($this->table('data_provenance')) $this->ensure('data_provenance', ['tenant_id' => $this->tenantId, 'entity_type' => 'tournament_ruleset', 'entity_id' => $tournament->id, 'source_type' => 'commercial_demo'], ['tenant_id' => $this->tenantId, 'entity_type' => 'tournament_ruleset', 'entity_id' => $tournament->id, 'source_type' => 'commercial_demo', 'source_id' => 'pickleball-standard:1.0', 'verification_status' => 'verified', 'actor_id' => $this->adminUserId, 'evidence' => json_encode(['country_code' => 'VN', 'ruleset' => 'pickleball-standard'])]);
        }
    }

    private function seedTenantDataPolicy(): void
    {
        if ($this->table('tenant_data_policies')) {
            service('tenantDataPolicy')->ensureDefaults($this->tenantId, $this->adminUserId);
        }
    }

    private function seedVolumeData(): void
    {
        $bookingIds = [];
        $statuses = ['completed', 'paid', 'checked_in', 'reserved', 'pending', 'cancelled'];

        for ($i = 1; $i <= 30; $i++) {
            $code = $this->uniqueTenantCode('bookings', 'booking_code', 'DEMO-VOL-BK-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT));
            $status = $statuses[$i % count($statuses)];
            $date = date('Y-m-d', strtotime(($i - 15) . ' days'));
            $hour = 7 + ($i % 12);
            $amount = 180000 + (($i % 4) * 40000);
            $paid = in_array($status, ['completed', 'paid', 'checked_in'], true) ? $amount : 0;
            $booking = $this->ensure('bookings', ['tenant_id' => $this->tenantId, 'booking_code' => $code], [
                'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'player_id' => null,
                'customer_name' => 'Demo Customer ' . $i, 'customer_phone' => '0904' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'customer_email' => 'booking' . $i . '@demo-pickleball.vn', 'booking_code' => $code, 'booking_date' => $date,
                'start_time' => sprintf('%02d:00:00', $hour), 'end_time' => sprintf('%02d:00:00', $hour + 1), 'duration_minutes' => 60,
                'total_amount' => $amount, 'deposit_amount' => $paid ? $amount / 2 : 0, 'paid_amount' => $paid, 'status' => $status,
                'payment_status' => $paid === $amount ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'), 'source' => ['admin', 'player_portal', 'public_web'][$i % 3],
                'note' => 'Booking volume demo #' . $i, 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId,
            ]);
            $bookingIds[] = $booking;
            $court = $this->courts[$i % count($this->courts)];
            $this->ensure('booking_items', ['tenant_id' => $this->tenantId, 'booking_id' => $booking, 'court_id' => $court], [
                'tenant_id' => $this->tenantId, 'booking_id' => $booking, 'court_id' => $court, 'start_time' => sprintf('%02d:00:00', $hour), 'end_time' => sprintf('%02d:00:00', $hour + 1),
                'price' => $amount, 'base_price' => $amount, 'dynamic_price' => $amount, 'status' => $status === 'cancelled' ? 'cancelled' : 'active',
            ]);
            $this->ensure('booking_qr_codes', ['tenant_id' => $this->tenantId, 'booking_id' => $booking], [
                'tenant_id' => $this->tenantId, 'booking_id' => $booking, 'qr_token' => $this->tenantKey('demo-vol-qr-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)), 'expired_at' => $date . ' 23:59:59', 'status' => $status === 'cancelled' ? 'revoked' : 'active',
            ]);
            $this->ensure('booking_logs', ['tenant_id' => $this->tenantId, 'booking_id' => $booking, 'action' => 'created'], [
                'tenant_id' => $this->tenantId, 'booking_id' => $booking, 'action' => 'created', 'new_status' => $status, 'message' => 'Tạo booking volume demo', 'created_by' => $this->adminUserId,
            ]);
        }

        for ($i = 1; $i <= 15; $i++) {
            $code = 'INV-DEMO-VOL-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $amount = 180000 + (($i % 4) * 40000);
            $invoice = $this->ensure('invoices', ['tenant_id' => $this->tenantId, 'invoice_code' => $code], [
                'tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'invoice_code' => $code, 'customer_type' => 'player', 'player_id' => $this->players[$i % count($this->players)],
                'ref_type' => 'booking', 'ref_id' => $bookingIds[$i % count($bookingIds)], 'subtotal' => $amount, 'discount_amount' => 0, 'total_amount' => $amount, 'paid_amount' => $amount, 'status' => 'paid', 'note' => 'Hóa đơn volume demo', 'created_by' => $this->adminUserId,
            ]);
            $payment = $this->ensure('payments', ['tenant_id' => $this->tenantId, 'payment_code' => 'PAY-DEMO-VOL-' . $i], [
                'tenant_id' => $this->tenantId, 'invoice_id' => $invoice, 'payment_code' => 'PAY-DEMO-VOL-' . $i, 'method' => ['cash', 'bank_qr', 'wallet', 'momo'][$i % 4], 'amount' => $amount, 'transaction_ref' => $this->tenantKey('TX-DEMO-VOL-' . $i), 'status' => 'success', 'idempotency_key' => $this->tenantKey('demo-volume-payment-' . $i), 'paid_at' => $this->now, 'created_by' => $this->adminUserId,
            ]);
            if ($i % 5 === 0) {
                $this->ensure('refunds', ['tenant_id' => $this->tenantId, 'payment_id' => $payment], ['tenant_id' => $this->tenantId, 'payment_id' => $payment, 'invoice_id' => $invoice, 'amount' => 30000, 'reason' => 'Hoàn phí volume demo', 'status' => 'completed', 'processed_by' => $this->adminUserId]);
            }
        }

        $category = $this->firstId('product_categories', ['tenant_id' => $this->tenantId, 'name_vi' => 'Đồ uống Demo']);
        $products = [];
        for ($i = 1; $i <= 10; $i++) {
            $product = $this->ensure('products', ['tenant_id' => $this->tenantId, 'sku' => 'DEMO-VOL-SKU-' . $i], ['tenant_id' => $this->tenantId, 'category_id' => $category, 'sku' => 'DEMO-VOL-SKU-' . $i, 'name_vi' => 'Sản phẩm Demo ' . $i, 'name_en' => 'Demo Product ' . $i, 'unit' => 'pcs', 'cost_price' => 5000 + $i * 500, 'sale_price' => 10000 + $i * 1000, 'status' => 'active']);
            $products[] = $product;
            $this->ensure('inventories', ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'product_id' => $product], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'product_id' => $product, 'quantity' => 100 + $i]);
        }
        for ($i = 1; $i <= 15; $i++) {
            $total = 20000 + $i * 1000;
            $order = $this->ensure('pos_orders', ['tenant_id' => $this->tenantId, 'order_code' => 'POS-DEMO-VOL-' . $i], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'player_id' => $this->players[$i % count($this->players)], 'order_code' => 'POS-DEMO-VOL-' . $i, 'total_amount' => $total, 'discount_amount' => 0, 'paid_amount' => $total, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'POS volume demo', 'created_by' => $this->adminUserId]);
            $product = $products[$i % count($products)];
            $this->ensure('pos_order_items', ['tenant_id' => $this->tenantId, 'order_id' => $order, 'product_id' => $product], ['tenant_id' => $this->tenantId, 'order_id' => $order, 'product_id' => $product, 'quantity' => 1, 'price' => $total, 'total' => $total]);
        }

        $coach = $this->firstId('coaches', ['tenant_id' => $this->tenantId, 'email' => 'coach.demo@demo-pickleball.vn']);
        for ($i = 1; $i <= 6; $i++) {
            $title = 'Clinic Demo Volume ' . $i;
            $session = $this->ensure('coaching_sessions', ['tenant_id' => $this->tenantId, 'title' => $title], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'coach_id' => $coach, 'court_id' => $this->courts[$i % count($this->courts)], 'title' => $title, 'session_type' => $i % 2 ? 'group' : 'private', 'session_date' => date('Y-m-d', strtotime('+' . (5 + $i) . ' days')), 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'capacity' => 6, 'price_per_player' => 300000 + $i * 10000, 'status' => $i % 3 === 0 ? 'completed' : 'open', 'notes' => 'Lớp coaching volume demo', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId]);
            $player = $this->players[$i % count($this->players)];
            $this->ensure('coaching_session_players', ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $player], ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $player, 'status' => 'approved', 'requested_at' => $this->now, 'approved_at' => $this->now, 'created_by' => $this->adminUserId]);
        }

        for ($i = 1; $i <= 8; $i++) {
            $title = 'Open Play Volume ' . $i;
            $session = $this->ensure('open_play_sessions', ['tenant_id' => $this->tenantId, 'title' => $title], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'host_player_id' => $this->players[$i], 'title' => $title, 'session_date' => date('Y-m-d', strtotime('+' . (4 + $i) . ' days')), 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'capacity' => 12, 'min_level' => 'beginner', 'max_level' => 'pro', 'price_per_player' => 120000, 'visibility' => 'public', 'status' => $i % 4 === 0 ? 'full' : 'open', 'created_by' => $this->adminUserId, 'updated_by' => $this->adminUserId]);
            foreach (array_slice($this->players, $i, 4) as $player) {
                $this->ensure('open_play_session_players', ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $player], ['tenant_id' => $this->tenantId, 'session_id' => $session, 'player_id' => $player, 'status' => 'approved', 'requested_at' => $this->now, 'approved_at' => $this->now, 'created_by' => $this->adminUserId]);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            $slug = 'demo-series-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $tournament = $this->ensure('tournaments', ['tenant_id' => $this->tenantId, 'slug_vi' => $slug], ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'organizer_id' => $this->tenantId, 'name_vi' => 'Demo Series ' . $i, 'name_en' => 'Demo Series ' . $i, 'slug_vi' => $slug, 'slug_en' => $slug, 'description_vi' => 'Giải volume demo.', 'start_date' => date('Y-m-d', strtotime('+' . (10 + $i) . ' days')), 'end_date' => date('Y-m-d', strtotime('+' . (11 + $i) . ' days')), 'registration_start' => $this->now, 'registration_end' => date('Y-m-d H:i:s', strtotime('+' . (7 + $i) . ' days')), 'max_teams' => 32, 'registration_fee' => 150000, 'status' => $i === 1 ? 'running' : ($i % 2 ? 'open' : 'completed'), 'verification_level' => $i % 2 ? 'verified' : 'club', 'created_by' => $this->adminUserId]);
            $category = $this->ensure('tournament_categories', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'name_vi' => 'Nội dung mở rộng'], ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'name_vi' => 'Nội dung mở rộng', 'name_en' => 'Open Division', 'category_type' => 'single_male', 'max_teams' => 32, 'min_rating' => 700, 'max_rating' => 2200, 'registration_fee' => 150000, 'status' => 'active']);
            foreach (array_slice($this->players, 0, 8) as $index => $player) {
                $this->ensure('tournament_registrations', ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'category_id' => $category, 'player_id' => $player], ['tenant_id' => $this->tenantId, 'tournament_id' => $tournament, 'category_id' => $category, 'player_id' => $player, 'contact_name' => 'Demo Player ' . ($index + 1), 'contact_phone' => '0905000' . str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'payment_status' => $index < 4 ? 'paid' : 'unpaid', 'approval_status' => 'approved', 'registration_status' => 'confirmed', 'eligibility_status' => 'passed']);
            }
        }

        for ($i = 1; $i <= 20; $i++) {
            $player = $this->players[$i % count($this->players)];
            $this->ensure('notifications', ['tenant_id' => $this->tenantId, 'user_id' => $this->adminUserId, 'template_code' => 'demo.volume.' . $i], ['tenant_id' => $this->tenantId, 'user_id' => $this->adminUserId, 'template_code' => 'demo.volume.' . $i, 'title' => 'Thông báo demo #' . $i, 'message' => 'Hoạt động mới trong hệ thống demo.', 'channel' => 'in_app', 'data' => json_encode(['demo' => true, 'index' => $i]), 'is_read' => $i % 3 === 0 ? 1 : 0, 'action_url' => '/admin/dashboard']);
            $post = $this->ensure('community_posts', ['tenant_id' => $this->tenantId, 'title' => 'Bài viết demo #' . $i], ['tenant_id' => $this->tenantId, 'player_id' => $player, 'type' => ['announcement', 'tip', 'event'][$i % 3], 'title' => 'Bài viết demo #' . $i, 'body' => 'Nội dung cộng đồng mẫu để kiểm tra danh sách và phân trang.', 'status' => 'published']);
            $this->ensure('community_comments', ['tenant_id' => $this->tenantId, 'post_id' => $post, 'player_id' => $this->players[($i + 1) % count($this->players)]], ['tenant_id' => $this->tenantId, 'post_id' => $post, 'player_id' => $this->players[($i + 1) % count($this->players)], 'body' => 'Bình luận mẫu #' . $i, 'status' => 'published']);
            $this->ensure('reviews', ['tenant_id' => $this->tenantId, 'player_id' => $player, 'entity_type' => 'booking', 'entity_id' => $bookingIds[$i % count($bookingIds)]], ['tenant_id' => $this->tenantId, 'player_id' => $player, 'entity_type' => 'booking', 'entity_id' => $bookingIds[$i % count($bookingIds)], 'rating' => 3 + ($i % 3), 'title' => 'Đánh giá demo #' . $i, 'body' => 'Dịch vụ tốt, dữ liệu mẫu phục vụ kiểm thử.', 'status' => 'published']);
        }
    }

    private function firstBookingId(): int
    {
        return $this->firstId('bookings', ['tenant_id' => $this->tenantId]);
    }

    private function firstId(string $table, array $where): int
    {
        if (! $this->table($table)) return 0;
        $row = $this->db->table($table)->where($where)->orderBy('id')->get(1)->getRow();
        return (int) ($row->id ?? 0);
    }

    private function tenantKey(string $key): string
    {
        return 't' . $this->tenantId . '-' . $key;
    }

    private function uniqueTenantCode(string $table, string $column, string $base): string
    {
        if (! $this->table($table)) {
            return $base;
        }
        $tenantMatch = $this->db->table($table)
            ->where('tenant_id', $this->tenantId)
            ->where($column, $base)
            ->get(1)
            ->getRow();
        if ($tenantMatch) {
            return $base;
        }
        $globalMatch = $this->db->table($table)->where($column, $base)->get(1)->getRow();
        return $globalMatch ? $this->tenantKey($base) : $base;
    }

    private function ensure(string $table, array $where, array $data): int
    {
        if (! $this->table($table)) return 0;
        $row = $this->db->table($table)->where($where)->get(1)->getRow();
        if ($row) return (int) ($row->id ?? 0);
        $payload = $this->filterColumns($table, $data + ['created_at' => $this->now, 'updated_at' => $this->now]);
        if (! $this->db->table($table)->insert($payload)) {
            throw new \RuntimeException('Không thể seed ' . $table . ': ' . json_encode($this->db->error(), JSON_UNESCAPED_UNICODE));
        }
        return (int) $this->db->insertID();
    }

    private function table(string $table): bool
    {
        return $this->db->tableExists($table);
    }

    private function filterColumns(string $table, array $data): array
    {
        return array_intersect_key($data, array_flip($this->db->getFieldNames($table)));
    }
}
