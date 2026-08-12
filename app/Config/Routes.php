<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ========== PUBLIC ROUTES ==========
$routes->get('/', 'Home::index');
$routes->get('/ranking', 'PublicPortal::ranking', ['namespace' => 'App\Controllers\Public']);
$routes->get('/players', 'PublicPortal::players', ['namespace' => 'App\Controllers\Public']);
$routes->get('/matches', 'PublicPortal::matches', ['namespace' => 'App\Controllers\Public']);
$routes->get('/clubs', 'PublicPortal::clubs', ['namespace' => 'App\Controllers\Public']);
$routes->get('/calendar', 'PublicPortal::calendar', ['namespace' => 'App\Controllers\Public']);
$routes->get('/live', 'PublicPortal::live', ['namespace' => 'App\Controllers\Public']);
$routes->get('/verify', 'PublicPortal::verify', ['namespace' => 'App\Controllers\Public']);
$routes->get('/solutions', 'Sales::index');
$routes->get('/solutions/(:segment)', 'Sales::product/$1');
$routes->get('/pricing', 'Sales::pricing');
$routes->get('/developers', 'Sales::developers');
$routes->get('/developers/docs', 'Sales::developers');
$routes->get('/api', 'Sales::developers');
$routes->get('/ranking/pro', 'Sales::rankingPro');
$routes->get('/demo', 'Sales::demo');
$routes->post('/demo', 'Sales::demo');
$routes->get('/login', 'AuthController::login');
$routes->post('/login', 'AuthController::loginPost');
$routes->get('/logout', 'AuthController::logout');
$routes->get('/forgot-password', 'AuthController::forgotPassword');
$routes->post('/forgot-password', 'AuthController::forgotPasswordPost');
$routes->get('/reset-password/(:any)', 'AuthController::resetPassword/$1');
$routes->post('/reset-password', 'AuthController::resetPasswordPost');
$routes->get('/locale/switch/(:any)', 'AuthController::switchLocale/$1');
$routes->get('/live-scores', 'LiveScores::index', ['namespace' => 'App\Controllers\Public']);
$routes->get('/live-scores/bracket', 'LiveScores::bracket', ['namespace' => 'App\Controllers\Public']);
$routes->get('/live-scores/tv', 'LiveScores::tv', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments', 'Tournaments::list', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments/(:segment)', 'Tournaments::detail/$1', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments/(:segment)/tv', 'Tournaments::tv/$1', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments/(:segment)/live', 'Tournaments::tv/$1', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments/(:segment)/register', 'Tournaments::register/$1', ['namespace' => 'App\Controllers\Public']);
$routes->post('/tournaments/(:segment)/register', 'Tournaments::submitRegistration/$1', ['namespace' => 'App\Controllers\Public']);

$routes->group('api/public/v1', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->get('home', 'PublicPortalApi::home');
    $routes->get('search', 'PublicPortalApi::search');
    $routes->get('players/(:num)/rating-history', 'PublicPortalApi::ratingHistory/$1');
    $routes->get('countries', 'PublicPortalApi::countries');
    $routes->get('players/(:segment)/ratings', 'RatingApi::publicRatings/$1');
    $routes->get('players/card/verify', 'PublicPortalApi::verifyPlayerCard');
    $routes->get('players/(:segment)/card', 'PublicPortalApi::playerCard/$1');
});

// Partner API — chỉ dữ liệu network công khai, luôn yêu cầu key + scope.
$routes->group('api/partner/v1', ['namespace' => 'App\Controllers\Api', 'filter' => 'apiratelimit'], function ($routes) {
    $routes->get('players/(:segment)', 'PartnerApiController::player/$1', ['filter' => 'partnerauth:players.read']);
    $routes->get('rankings', 'PartnerApiController::rankings', ['filter' => 'partnerauth:rankings.read']);
    $routes->get('clubs', 'PartnerApiController::clubs', ['filter' => 'partnerauth:clubs.read']);
    $routes->get('tournaments', 'PartnerApiController::tournaments', ['filter' => 'partnerauth:tournaments.read']);
});

// ========== API ROUTES ==========
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    // Auth
    $routes->post('auth/login', 'AuthApi::login');
    $routes->post('auth/refresh', 'AuthApi::refresh', ['filter' => 'apiauth']);

    // Booking public routes (no auth)
    $routes->get('booking/available-slots', 'BookingApi::availableSlots');
    $routes->post('booking/checkin', 'BookingApi::checkInQr');
    $routes->get('live-scores', 'LiveScores::index');
    $routes->get('live-scores/tv', 'LiveScores::tv');
    $routes->get('live-scores/bracket', 'LiveScores::bracket');
    $routes->get('network/clubs', 'PlatformClubApi::index');

        // Facility API routes
        $routes->get('facilities', 'FacilityApi::index');
        $routes->get('facilities/(:num)', 'FacilityApi::show/$1');
        $routes->get('facilities/(:num)/dashboard', 'FacilityApi::dashboard/$1');
    $routes->get('facilities/(:num)/branches', 'FacilityApi::branches/$1');
    $routes->get('facilities/(:num)/clubs', 'FacilityApi::clubs/$1');
        $routes->get('facilities/branch/(:num)', 'FacilityApi::branchDetail/$1');
        $routes->get('facilities/branch/(:num)/hours', 'FacilityApi::branchOpeningHours/$1');
        $routes->get('facilities/branch/(:num)/holidays', 'FacilityApi::branchHolidays/$1');

        // Court API routes
        $routes->get('facilities/court-types', 'FacilityApi::courtTypes');
        $routes->get('facilities/court-statuses', 'FacilityApi::courtStatuses');
        $routes->get('facilities/branch/(:num)/courts', 'FacilityApi::courts/$1');
        $routes->get('facilities/court/(:num)', 'FacilityApi::courtDetail/$1');

        // Realtime API routes
        $routes->get('facilities/branch/(:num)/realtime', 'FacilityApi::realtimeStatus/$1');
        $routes->get('facilities/branch/(:num)/sessions', 'FacilityApi::activeSessions/$1');
        $routes->get('facilities/branch/(:num)/timeline', 'FacilityApi::courtTimeline/$1');

        // Device API routes
        $routes->get('facilities/branch/(:num)/devices', 'FacilityApi::devices/$1');
        $routes->get('facilities/device/(:num)', 'FacilityApi::deviceDetail/$1');
        $routes->get('facilities/device/(:num)/logs', 'FacilityApi::deviceLogs/$1');

        // Report API routes
        $routes->get('facilities/branch/(:num)/report', 'FacilityApi::report/$1');
        $routes->get('facilities/branch/(:num)/peak-hours', 'FacilityApi::peakHours/$1');
        $routes->get('facilities/branch/(:num)/court-ranking', 'FacilityApi::courtRanking/$1');
        $routes->get('facilities/branch/(:num)/revenue', 'FacilityApi::revenueByCourt/$1');
        $routes->get('facilities/branch/(:num)/utilization', 'FacilityApi::utilization/$1');

        // Protected API routes
        $routes->group('', ['filter' => 'apiauth'], function ($routes) {
        $routes->get('tenants', 'TenantApi::index');
        $routes->get('tenants/(:num)', 'TenantApi::show/$1');
        $routes->get('branches', 'BranchApi::index');
        $routes->get('branches/(:num)', 'BranchApi::show/$1');
        $routes->get('users/profile', 'UserApi::profile');
        $routes->get('users/(:num)', 'UserApi::show/$1');
        $routes->get('settings', 'SettingApi::index');
        $routes->get('court-types', 'CourtApi::courtTypes');
        $routes->get('courts/available', 'CourtApi::available');
        $routes->get('courts', 'CourtApi::index');
        $routes->get('courts/(:num)', 'CourtApi::show/$1');
        $routes->get('branches/(:num)/courts', 'CourtApi::getByBranch/$1');
        $routes->post('facilities/device/(:num)/toggle', 'FacilityApi::toggleDevice/$1');

        // Tournament scheduling API routes
        $routes->post('tournaments/(:num)/auto-schedule', 'TournamentSchedulerApi::autoSchedule/$1');
        $routes->post('tournament-matches/(:num)/move', 'TournamentSchedulerApi::moveMatch/$1');
        $routes->post('tournament-matches/(:num)/lock', 'TournamentSchedulerApi::lockMatch/$1');
        $routes->post('tournament-matches/(:num)/unlock', 'TournamentSchedulerApi::unlockMatch/$1');
        $routes->get('tournaments/(:num)/conflicts', 'TournamentSchedulerApi::conflicts/$1');

        // Booking API routes (authenticated)
        $routes->group('bookings', function ($routes) {
            $routes->get('/', 'BookingApi::index');
            $routes->post('/', 'BookingApi::create');
            $routes->get('(:num)', 'BookingApi::detail/$1');
            $routes->post('(:num)/cancel', 'BookingApi::cancel/$1');
        });
        $routes->post('live-scores/(:num)', 'LiveScores::update/$1');

        // Unified match and immutable official-result workflow
        $routes->post('matches', 'UnifiedMatchApi::create');
        $routes->get('matches/(:num)', 'UnifiedMatchApi::show/$1');
        $routes->post('matches/(:num)/result', 'UnifiedMatchApi::submit/$1');
        $routes->post('matches/(:num)/confirm', 'UnifiedMatchApi::confirm/$1');
        $routes->post('matches/(:num)/official', 'UnifiedMatchApi::official/$1');
        $routes->post('matches/(:num)/dispute', 'MatchGovernanceApi::dispute/$1');
        $routes->post('match-disputes/(:num)/resolve', 'MatchGovernanceApi::resolve/$1');

        // Trust & competition foundations: authority, provenance, appeals and corrections.
        $routes->get('provenance/(:segment)/(:num)', 'FoundationApi::provenance/$1/$2');
        $routes->post('governance/authorities', 'FoundationApi::authority');
        $routes->post('governance/decisions', 'FoundationApi::decision');
        $routes->post('governance/sanctions/(:num)/transition', 'FoundationApi::sanction/$1');
        $routes->post('governance/appeals', 'FoundationApi::appeal');
        $routes->post('governance/appeals/(:num)/transition', 'FoundationApi::appealTransition/$1');
        $routes->post('matches/(:num)/corrections', 'FoundationApi::correction/$1');
        $routes->post('correction-requests/(:num)/approve', 'FoundationApi::approveCorrection/$1');
        $routes->post('correction-requests/(:num)/reject', 'FoundationApi::rejectCorrection/$1');
        $routes->post('tournament-matches/(:num)/sync-unified', 'TournamentMatchNetworkApi::sync/$1');
        $routes->post('tournament-matches/(:num)/official-result', 'TournamentMatchNetworkApi::official/$1');

        // Player mobile API routes
        $routes->get('player/profile', 'PlayerApi::profile');
        $routes->post('player/update-profile', 'PlayerApi::updateProfile');
        $routes->get('player/wallet', 'PlayerApi::wallet');
        $routes->get('player/ranking', 'PlayerApi::ranking');
        $routes->get('players/(:num)/ratings', 'RatingApi::profile/$1');
        $routes->get('players/(:num)/ratings/(:segment)/history', 'RatingApi::history/$1/$2');
        $routes->post('player/skill-assessments', 'RatingApi::assessment');
        $routes->post('player/rating-claims', 'RatingApi::claim');
        $routes->post('rating/eligibility', 'RatingApi::eligibility');
        $routes->post('rating/imports', 'RatingApi::importUpload');
        $routes->post('rating/imports/(:num)/(:segment)', 'RatingApi::importStep/$1/$2');
        $routes->get('rankings/leaderboard', 'RankingApi::leaderboard');
        $routes->post('network/clubs', 'PlatformClubApi::create');
        $routes->post('network/clubs/(:num)/link', 'PlatformClubApi::link/$1');

        // Coaching and competition mobile surface
        $routes->get('coaching/sessions', 'CoachingApi::index');
        $routes->post('coaching/sessions/(:num)/join', 'CoachingApi::join/$1');
        $routes->post('coaching/entries/(:num)/pay', 'CoachingApi::pay/$1');
        $routes->get('competitions', 'CompetitionApi::index');
        $routes->get('competitions/(:num)', 'CompetitionApi::detail/$1');
        $routes->post('competitions/ladder/(:num)/respond', 'CompetitionApi::ladderRespond/$1');
        $routes->post('competitions/participants/(:num)/pay', 'CompetitionApi::payEntry/$1');
        $routes->get('community/posts', 'CommunityApi::index');
        $routes->post('community/posts', 'CommunityApi::store');
        $routes->post('community/posts/(:num)/comments', 'CommunityApi::comment/$1');
        $routes->post('community/posts/(:num)/reactions', 'CommunityApi::react/$1');
        $routes->get('ai-scheduling/requests', 'AiSchedulingApi::index');
        $routes->post('ai-scheduling/requests', 'AiSchedulingApi::store');
        $routes->post('ai-scheduling/requests/(:num)/run', 'AiSchedulingApi::run/$1');
        $routes->get('livestream/channels', 'LivestreamApi::index');
    });
});

// ========== ADMIN ROUTES ==========
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function ($routes) {
    // Auth protected routes
    $routes->group('', ['filter' => 'auth'], function ($routes) {
        $routes->get('rating', 'RatingGovernanceController::index', ['filter' => 'permission:rating.view']);
        $routes->get('data-quality', 'DataQualityController::index', ['filter' => 'permission:dashboard.view']);
        $routes->get('governance', 'GovernanceController::index', ['filter' => 'permission:rating.review']);
        $routes->post('governance/disputes/(:num)/resolve', 'GovernanceController::resolveDispute/$1', ['filter' => 'permission:rating.review']);
        $routes->post('governance/corrections/(:num)/approve', 'GovernanceController::approveCorrection/$1', ['filter' => 'permission:rating.review']);
        $routes->post('governance/corrections/(:num)/reject', 'GovernanceController::rejectCorrection/$1', ['filter' => 'permission:rating.review']);
        $routes->get('queue', 'QueueMonitorController::index', ['filter' => 'permission:dashboard.view']);
        $routes->post('queue/(:num)/retry', 'QueueMonitorController::retry/$1', ['filter' => 'permission:dashboard.view']);
        $routes->post('queue/(:num)/dead-letter', 'QueueMonitorController::deadLetter/$1', ['filter' => 'permission:dashboard.view']);
        $routes->post('rating/adjust', 'RatingGovernanceController::adjust', ['filter' => 'permission:rating.adjust']);
        $routes->post('rating/claims/(:num)/verify', 'RatingGovernanceController::verifyClaim/$1', ['filter' => 'permission:rating.review']);
        $routes->post('rating/flags/(:num)/resolve', 'RatingGovernanceController::resolveFlag/$1', ['filter' => 'permission:rating.review']);
        $routes->get('dashboard', 'DashboardController::index');

        $routes->group('ops', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('available-courts', 'OpsAjaxController::availableCourts');
            $routes->match(['get', 'post'], 'pricing-test', 'OpsAjaxController::pricingTest');
            $routes->get('booking-drawer/(:num)', 'OpsAjaxController::bookingDrawer/$1');
            $routes->get('court-drawer/(:num)', 'OpsAjaxController::courtDrawer/$1');
            $routes->get('court-pricing-rules/(:num)', 'OpsAjaxController::courtPricingRules/$1');
            $routes->get('reschedule-preview/(:num)', 'OpsAjaxController::reschedulePreview/$1');
        });

        // Facility routes (Cluster management)
        $routes->group('facilities', ['filter' => 'permission:facilities.view'], function ($routes) {
            $routes->get('/', 'FacilitiesController::index');
            $routes->get('create', 'FacilitiesController::create');
            $routes->post('create', 'FacilitiesController::store');
            $routes->get('edit/(:num)', 'FacilitiesController::edit/$1');
            $routes->post('update/(:num)', 'FacilitiesController::update/$1');
            $routes->get('delete/(:num)', 'FacilitiesController::delete/$1');
            $routes->get('dashboard/(:num)', 'FacilitiesController::dashboard/$1');
            $routes->get('branches/(:num)', 'FacilitiesController::branches/$1');
            $routes->get('clubs/(:num)', 'FacilitiesController::clubs/$1');
            $routes->post('clubs/(:num)/assign', 'FacilitiesController::assignClub/$1');
            $routes->post('clubs/(:num)/remove/(:num)', 'FacilitiesController::removeClub/$1/$2');
        });

        // Court grid & timeline routes
        $routes->group('courts', function ($routes) {
            $routes->get('grid/(:num)', 'FacilitiesController::courtGrid/$1');
            $routes->get('timeline/(:num)', 'FacilitiesController::courtTimeline/$1');
            $routes->get('realtime/(:num)', 'FacilitiesController::realtimeStatus/$1');
            $routes->get('report/(:num)', 'FacilitiesController::report/$1');
            $routes->get('devices/(:num)', 'FacilitiesController::devices/$1');
            $routes->post('toggle-device/(:num)', 'FacilitiesController::toggleDevice/$1');
        });

        // Tenant routes
        $routes->group('tenants', ['filter' => 'permission:tenants.view'], function ($routes) {
            $routes->get('/', 'TenantController::index');
            $routes->get('create', 'TenantController::create');
            $routes->post('create', 'TenantController::store');
            $routes->get('edit/(:num)', 'TenantController::edit/$1');
            $routes->post('update/(:num)', 'TenantController::update/$1');
            $routes->get('delete/(:num)', 'TenantController::delete/$1');
            $routes->get('select', 'TenantController::select');
            $routes->get('set-session/(:num)', 'TenantController::setSession/$1');
        });

        // Branch routes
        $routes->group('branches', ['filter' => 'permission:branches.view'], function ($routes) {
            $routes->get('/', 'BranchController::index');
            $routes->get('create', 'BranchController::create');
            $routes->post('create', 'BranchController::store');
            $routes->get('edit/(:num)', 'BranchController::edit/$1');
            $routes->post('update/(:num)', 'BranchController::update/$1');
            $routes->post('delete/(:num)', 'BranchController::delete/$1');
            $routes->get('hours/(:num)', 'BranchController::hours/$1');
            $routes->post('save-hours/(:num)', 'BranchController::saveHours/$1');
            $routes->get('holidays/(:num)', 'BranchController::holidays/$1');
            $routes->post('store-holiday/(:num)', 'BranchController::storeHoliday/$1');
            $routes->post('delete-holiday/(:num)/(:num)', 'BranchController::deleteHoliday/$1/$2');
        });

        // User routes
        $routes->group('users', ['filter' => 'permission:users.view'], function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->get('create', 'UserController::create');
            $routes->post('create', 'UserController::store');
            $routes->get('edit/(:num)', 'UserController::edit/$1');
            $routes->post('update/(:num)', 'UserController::update/$1');
            $routes->get('delete/(:num)', 'UserController::delete/$1');
        });

        // Role routes
        $routes->group('roles', ['filter' => 'permission:roles.view'], function ($routes) {
            $routes->get('/', 'RoleController::index');
            $routes->get('create', 'RoleController::create');
            $routes->post('create', 'RoleController::store');
            $routes->get('edit/(:num)', 'RoleController::edit/$1');
            $routes->post('update/(:num)', 'RoleController::update/$1');
            $routes->get('delete/(:num)', 'RoleController::delete/$1');
            $routes->get('permissions/(:num)', 'RoleController::permissions/$1');
            $routes->post('permissions/(:num)', 'RoleController::updatePermissions/$1');
        });

        // Settings routes
        $routes->group('settings', ['filter' => 'permission:settings.view'], function ($routes) {
            $routes->get('/', 'SettingController::index');
            $routes->post('update', 'SettingController::update');
        });

        // Court routes
        $routes->group('courts', ['filter' => 'permission:courts.view'], function ($routes) {
            $routes->get('/', 'CourtsController::index');
            $routes->get('create', 'CourtsController::create');
            $routes->post('create', 'CourtsController::store');
            $routes->get('edit/(:num)', 'CourtsController::edit/$1');
            $routes->post('update/(:num)', 'CourtsController::update/$1');
            $routes->get('delete/(:num)', 'CourtsController::delete/$1');
            $routes->post('status/(:num)', 'CourtsController::status/$1');
            $routes->post('upload-image/(:num)', 'CourtsController::uploadImage/$1');
            $routes->post('delete-image/(:num)', 'CourtsController::deleteImage/$1');
            $routes->post('set-primary-image/(:num)/(:num)', 'CourtsController::setPrimaryImage/$1/$2');
            $routes->get('maintenance/(:num)', 'CourtsController::maintenance/$1');
            $routes->post('store-maintenance/(:num)', 'CourtsController::storeMaintenance/$1');
            $routes->post('update-maintenance-status/(:num)', 'CourtsController::updateMaintenanceStatus/$1');
            $routes->get('calendar', 'CourtsController::calendar');
        });

        // Booking routes
        $routes->group('bookings', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'BookingsController::index');
            $routes->get('calendar', 'BookingsController::calendar');
            $routes->get('create', 'BookingsController::create');
            $routes->post('create', 'BookingsController::store');
            $routes->get('show/(:num)', 'BookingsController::show/$1');
            $routes->post('check-in/(:num)', 'BookingsController::checkIn/$1');
            $routes->post('cancel/(:num)', 'BookingsController::cancel/$1');
            $routes->get('reschedule/(:num)', 'BookingsController::reschedule/$1');
            $routes->post('reschedule/(:num)', 'BookingsController::updateReschedule/$1');
            $routes->post('checkin-qr', 'BookingsController::checkInQr');
        });

        // Recurring booking templates and due occurrence generation
        $routes->group('recurring-bookings', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'RecurringBookingsController::index');
            $routes->post('store', 'RecurringBookingsController::store');
            $routes->post('status/(:num)', 'RecurringBookingsController::status/$1');
            $routes->post('process-due', 'RecurringBookingsController::processDue');
        });

        // Waitlist: tenant-scoped slot requests and atomic claim
        $routes->group('waitlist', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'WaitlistController::index');
            $routes->post('store', 'WaitlistController::store');
            $routes->post('notify-next', 'WaitlistController::notifyNext');
            $routes->post('claim/(:num)', 'WaitlistController::claim/$1');
            $routes->post('cancel/(:num)', 'WaitlistController::cancel/$1');
            $routes->post('expire', 'WaitlistController::expire');
        });

        // Walk-in desk operations: create, check-in, checkout and cancel.
        $routes->group('walk-ins', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'WalkInsController::index');
            $routes->post('store', 'WalkInsController::store');
            $routes->post('check-in/(:num)', 'WalkInsController::checkIn/$1');
            $routes->post('checkout/(:num)', 'WalkInsController::checkout/$1');
            $routes->post('cancel/(:num)', 'WalkInsController::cancel/$1');
        });

        $routes->group('front-desk', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'FrontDeskController::index');
        });

        $routes->group('daily-closing', ['filter' => 'permission:payments.view'], function ($routes) {
            $routes->get('/', 'DailyClosingController::index');
            $routes->post('close', 'DailyClosingController::close');
            $routes->post('reopen', 'DailyClosingController::reopen');
        });

        $routes->group('operations-report', ['filter' => 'permission:dashboard.view'], function ($routes) {
            $routes->get('/', 'OperationsReportController::index');
            $routes->get('csv', 'OperationsReportController::csv');
        });

        $routes->group('open-play', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'OpenPlayController::index');
            $routes->post('store', 'OpenPlayController::store');
            $routes->post('join/(:num)', 'OpenPlayController::join/$1');
            $routes->post('approve/(:num)', 'OpenPlayController::approve/$1');
            $routes->post('leave/(:num)', 'OpenPlayController::leave/$1');
            $routes->post('rotation/(:num)', 'OpenPlayController::generateRotation/$1');
        });

        $routes->group('coaching', ['filter' => 'permission:bookings.view'], function ($routes) {
            $routes->get('/', 'CoachingController::index');
            $routes->post('coaches/store', 'CoachingController::storeCoach');
            $routes->post('availability/store', 'CoachingController::storeAvailability');
            $routes->post('blackouts/store', 'CoachingController::storeBlackout');
            $routes->post('sessions/store', 'CoachingController::storeSession');
            $routes->post('approve/(:num)', 'CoachingController::approve/$1');
            $routes->post('cancel/(:num)', 'CoachingController::cancel/$1');
            $routes->post('attendance/(:num)', 'CoachingController::attendance/$1');
        });

        $routes->group('competitions', ['filter' => 'permission:tournaments.view'], function ($routes) {
            $routes->get('/', 'CompetitionsController::index');
            $routes->post('events/store', 'CompetitionsController::storeEvent');
            $routes->post('(:num)/participants/store', 'CompetitionsController::addParticipant/$1');
            $routes->post('(:num)/entry-fee', 'CompetitionsController::updateEntryFee/$1');
            $routes->post('(:num)/generate', 'CompetitionsController::generate/$1');
            $routes->post('fixtures/(:num)/result', 'CompetitionsController::result/$1');
            $routes->post('participants/(:num)/checkin', 'CompetitionsController::checkin/$1');
            $routes->post('(:num)/ladder/challenge', 'CompetitionsController::ladderChallenge/$1');
            $routes->post('ladder/(:num)/result', 'CompetitionsController::ladderResult/$1');
            $routes->post('ladder/(:num)/respond', 'CompetitionsController::ladderRespond/$1');
        });

        $routes->group('growth', ['filter' => 'permission:players.view'], function ($routes) {
            $routes->get('/', 'GrowthController::index');
            $routes->post('promotions/store', 'GrowthController::storePromotion');
            $routes->post('referrals/qualify/(:num)', 'GrowthController::qualifyReferral/$1');
            $routes->post('referrals/reward/(:num)', 'GrowthController::rewardReferral/$1');
            $routes->post('reviews/(:num)/status', 'GrowthController::reviewStatus/$1');
        });

        // Score input & live tournament scoring
        $routes->group('scores', ['filter' => 'permission:scores.view'], function ($routes) {
            $routes->get('/', 'Scores::index');
            $routes->get('(:num)', 'Scores::edit/$1');
            $routes->post('(:num)/start', 'Scores::start/$1', ['filter' => 'permission:scores.input']);
            $routes->post('(:num)/update', 'Scores::update/$1', ['filter' => 'permission:scores.input']);
            $routes->post('(:num)/finish', 'Scores::finish/$1', ['filter' => 'permission:scores.input']);
            $routes->post('(:num)/lock', 'Scores::lock/$1', ['filter' => 'permission:scores.input']);
            $routes->post('(:num)/unlock', 'Scores::unlock/$1', ['filter' => 'permission:scores.input']);
        });

        // Tournament setup routes (yêu cầu gói có tính năng giải đấu)
        $routes->group('tournaments', ['filter' => ['permission:tournaments.view', 'plan:tournament']], function ($routes) {
            $routes->get('/', 'Tournaments::index');
            $routes->get('export', 'Tournaments::export');
            $routes->get('create', 'Tournaments::create');
            $routes->post('store', 'Tournaments::store');
            $routes->get('edit/(:num)', 'Tournaments::edit/$1');
            $routes->post('update/(:num)', 'Tournaments::update/$1');
            $routes->get('show/(:num)', 'Tournaments::show/$1');
            $routes->get('registrations', 'Tournaments::registrationHub');
            $routes->get('registrations/(:num)', 'Tournaments::registrations/$1');
            $routes->get('registrations/(:num)/export', 'Tournaments::exportRegistrations/$1');
            $routes->post('registrations/(:num)/store', 'Tournaments::registerAthlete/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('registrations/(:num)/update', 'Tournaments::updateRegistration/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('registrations/(:num)/checkin', 'Tournaments::checkinRegistration/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('registrations/(:num)/delete', 'Tournaments::deleteRegistration/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('registrations/(:num)/approve', 'Tournaments::approveRegistration/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('registrations/(:num)/reject', 'Tournaments::rejectRegistration/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('open/(:num)', 'Tournaments::open/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('close/(:num)', 'Tournaments::close/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('start/(:num)', 'Tournaments::start/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('complete/(:num)', 'Tournaments::complete/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('cancel/(:num)', 'Tournaments::cancel/$1', ['filter' => 'permission:tournaments.manage']);
        });

        $routes->group('tournaments/control-room', ['filter' => ['permission:tournaments.view', 'plan:tournament']], function ($routes) {
            $routes->get('/', 'TournamentOperationsController::index');
            $routes->get('data', 'TournamentOperationsController::data');
            $routes->post('status/(:num)', 'TournamentOperationsController::status/$1', ['filter' => 'permission:tournaments.manage']);
            $routes->post('call/(:num)', 'TournamentOperationsController::call/$1', ['filter' => 'permission:tournaments.manage']);
        });
        $routes->group('tournaments/bracket', ['filter' => ['permission:tournaments.view', 'plan:tournament']], function ($routes) {
            $routes->get('/', 'TournamentBracketController::index');
            $routes->get('export/(:num)', 'TournamentBracketController::export/$1');
            $routes->post('rerun/(:num)', 'TournamentBracketController::rerun/$1', ['filter' => 'permission:tournaments.manage']);
        });
        $routes->group('tournament-templates', ['filter' => ['permission:tournaments.view', 'plan:tournament']], function ($routes) {
            $routes->get('/', 'TournamentTemplatesController::index');
            $routes->post('save', 'TournamentTemplatesController::saveFromTournament', ['filter' => 'permission:tournaments.manage']);
            $routes->get('use/(:num)', 'TournamentTemplatesController::use/$1');
            $routes->post('use/(:num)', 'TournamentTemplatesController::create/$1', ['filter' => 'permission:tournaments.manage']);
        });
        $routes->group('print-center', ['filter' => ['permission:tournaments.view', 'plan:tournament']], function ($routes) {
            $routes->get('/', 'PrintCenterController::index');
            $routes->get('print', 'PrintCenterController::print');
        });

        // Dynamic pricing routes
        $routes->group('pricing-rules', ['filter' => 'permission:pricing-rules.view'], function ($routes) {
            $routes->get('/', 'PricingRulesController::index');
            $routes->get('create', 'PricingRulesController::create');
            $routes->post('store', 'PricingRulesController::store');
            $routes->get('edit/(:num)', 'PricingRulesController::edit/$1');
            $routes->post('update/(:num)', 'PricingRulesController::update/$1');
            $routes->get('toggle/(:num)', 'PricingRulesController::toggle/$1');
            $routes->get('delete/(:num)', 'PricingRulesController::delete/$1');
            $routes->match(['get', 'post'], 'test', 'PricingRulesController::test');
        });

        // Player CRM routes
        $routes->group('players', ['filter' => 'permission:players.view'], function ($routes) {
            $routes->get('dashboard', 'PlayersController::dashboard');
            $routes->get('ranking', 'PlayersController::ranking');
            $routes->get('/', 'PlayersController::index');
            $routes->get('create', 'PlayersController::create');
            $routes->post('store', 'PlayersController::store');
            $routes->get('profile/(:num)', 'PlayersController::profile/$1');
            $routes->post('profile/(:num)/check-in', 'PlayersController::checkIn/$1');
            $routes->get('match-history/(:num)', 'PlayersController::matchHistory/$1');
            $routes->post('match-history/(:num)', 'PlayersController::storeMatch/$1');
            $routes->get('edit/(:num)', 'PlayersController::edit/$1');
            $routes->post('update/(:num)', 'PlayersController::update/$1');
            $routes->get('delete/(:num)', 'PlayersController::delete/$1');
            $routes->get('wallet/(:num)', 'PlayersController::wallet/$1');
            $routes->post('topup/(:num)', 'PlayersController::topup/$1');
            $routes->post('adjust-wallet/(:num)', 'PlayersController::adjustWallet/$1');
            $routes->get('booking-history/(:num)', 'PlayersController::bookingHistory/$1');
        });

        // CRM customer directory (Customer is separate from Player Registry)
        $routes->group('customers', ['filter' => 'permission:players.view'], function ($routes) {
            $routes->get('/', 'CustomersController::index');
            $routes->get('show/(:num)', 'CustomersController::show/$1');
            $routes->post('status/(:num)', 'CustomersController::updateStatus/$1');
            $routes->post('tags/(:num)', 'CustomersController::syncTags/$1');
            $routes->post('tags/(:num)/remove/(:num)', 'CustomersController::unlinkTag/$1/$2');
            $routes->post('note/(:num)', 'CustomersController::addNote/$1');
            $routes->get('create-booking/(:num)', 'CustomersController::quickCreateBooking/$1');
        });

        $routes->group('crm-campaigns', ['filter' => 'permission:players.view'], function ($routes) {
            $routes->get('/', 'CrmCampaignsController::index');
            $routes->post('store', 'CrmCampaignsController::store');
            $routes->post('launch/(:num)', 'CrmCampaignsController::launch/$1');
        });

        // Club, team and social match routes
        $routes->group('clubs', ['filter' => 'permission:clubs.view'], function ($routes) {
            $routes->get('/', 'ClubsController::index');
            $routes->get('create', 'ClubsController::create');
            $routes->post('store', 'ClubsController::store');
            $routes->get('edit/(:num)', 'ClubsController::edit/$1');
            $routes->post('update/(:num)', 'ClubsController::update/$1');
            $routes->get('delete/(:num)', 'ClubsController::delete/$1');
        });

        $routes->group('teams', ['filter' => 'permission:teams.view'], function ($routes) {
            $routes->get('/', 'TeamsController::index');
            $routes->get('show/(:num)', 'TeamsController::show/$1');
            $routes->post('status/(:num)', 'TeamsController::status/$1');
        });

        $routes->group('matches', ['filter' => 'permission:matches.view'], function ($routes) {
            $routes->get('/', 'MatchRequestsController::index');
            $routes->get('show/(:num)', 'MatchRequestsController::show/$1');
            $routes->post('approve/(:num)', 'MatchRequestsController::approve/$1');
            $routes->post('cancel/(:num)', 'MatchRequestsController::cancel/$1');
            $routes->post('convert/(:num)', 'MatchRequestsController::convert/$1');
        });

        // Membership routes
        $routes->group('memberships', ['filter' => 'permission:memberships.view'], function ($routes) {
            $routes->get('/', 'MembershipsController::index');
            $routes->get('renewals', 'MembershipsController::renewals');
            $routes->post('renew/(:num)', 'MembershipsController::renew/$1');
            $routes->get('create', 'MembershipsController::create');
            $routes->post('store', 'MembershipsController::store');
            $routes->get('cancel/(:num)', 'MembershipsController::cancel/$1');
            $routes->get('packages', 'MembershipsController::packages');
            $routes->get('create-package', 'MembershipsController::createPackage');
            $routes->post('store-package', 'MembershipsController::storePackage');
            $routes->get('edit-package/(:num)', 'MembershipsController::editPackage/$1');
            $routes->post('update-package/(:num)', 'MembershipsController::updatePackage/$1');
            $routes->get('delete-package/(:num)', 'MembershipsController::deletePackage/$1');
        });

        // Tournament scheduling routes
        $routes->group('tournaments/scheduler', ['filter' => 'permission:tournaments.manage'], function ($routes) {
            $routes->get('/', 'TournamentSchedulerController::index');
            $routes->post('auto-schedule', 'TournamentSchedulerController::autoSchedule');
            $routes->post('rerun-unlocked', 'TournamentSchedulerController::rerunUnlocked');
            $routes->post('schedule/(:num)', 'TournamentSchedulerController::schedule/$1');
            $routes->post('manual', 'TournamentSchedulerController::manualMatch');
            $routes->post('matches/(:num)/participants', 'TournamentSchedulerController::assignParticipants/$1');
            $routes->post('publish/(:num)', 'TournamentSchedulerController::publish/$1');
            $routes->post('unpublish/(:num)', 'TournamentSchedulerController::unpublish/$1');
            $routes->get('export', 'TournamentSchedulerController::export');
            $routes->post('lock/(:num)', 'TournamentSchedulerController::lock/$1');
            $routes->post('unlock/(:num)', 'TournamentSchedulerController::unlock/$1');
            $routes->post('team-group', 'TournamentSchedulerController::moveTeam');
        });

        $routes->group('ai-scheduling', ['filter' => 'permission:tournaments.manage'], function ($routes) {
            $routes->get('/', 'AiSchedulingController::index');
            $routes->post('store', 'AiSchedulingController::store');
            $routes->post('(:num)/run', 'AiSchedulingController::run/$1');
        });

        $routes->group('livestream', ['filter' => 'permission:tournaments.manage'], function ($routes) {
            $routes->get('/', 'LivestreamController::index');
            $routes->post('store', 'LivestreamController::store');
            $routes->post('(:num)/status', 'LivestreamController::status/$1');
        });

        $routes->group('webhooks', ['filter' => 'permission:settings.view'], function ($routes) {
            $routes->get('/', 'WebhookController::index');
            $routes->post('store', 'WebhookController::store');
            $routes->post('(:num)/status', 'WebhookController::status/$1');
        });

        $routes->group('integrations', ['filter' => 'permission:settings.view'], function ($routes) {
            $routes->get('/', 'IntegrationsController::index');
            $routes->post('keys', 'IntegrationsController::store');
            $routes->post('keys/(:num)/revoke', 'IntegrationsController::revoke/$1');
            $routes->post('health/(:num)', 'IntegrationsController::health/$1');
        });

        // POS routes (quầy bán hàng — yêu cầu quyền POS + gói có tính năng POS)
        $routes->group('pos', ['filter' => ['permission:pos.access', 'plan:pos']], function ($routes) {
            $routes->get('/', 'PosController::index');
            $routes->get('counter', 'PosController::index');
            $routes->get('inventory', 'PosController::inventory');
            $routes->get('inventory/history', 'PosController::inventoryHistory');
            $routes->get('getStock/(:num)', 'PosController::getStock/$1');
            $routes->post('importStock', 'PosController::importStock');
            $routes->post('adjustStock', 'PosController::adjustStock');
            $routes->get('getOrder/(:num)', 'PosController::getOrder/$1');
            $routes->post('addItem/(:num)', 'PosController::addItem/$1');
            $routes->post('removeItem/(:num)/(:num)', 'PosController::removeItem/$1/$2');
            $routes->post('updateItem/(:num)', 'PosController::updateItem/$1');
            $routes->post('attachBooking/(:num)', 'PosController::attachBooking/$1');
            $routes->post('attachPlayer/(:num)', 'PosController::attachPlayer/$1');
            $routes->get('searchProducts/(:num)', 'PosController::searchProducts/$1');
            $routes->get('searchBookings', 'PosController::searchBookings');
            $routes->get('searchPlayers', 'PosController::searchPlayers');
            $routes->post('checkout/(:num)', 'PosController::checkout/$1');
            $routes->post('cancel/(:num)', 'PosController::cancel/$1');
        });

        // Payment routes (hóa đơn & thanh toán)
        $routes->group('payments', ['filter' => 'permission:payments.view'], function ($routes) {
            $routes->get('/', 'PaymentController::index');
            $routes->get('detail/(:num)', 'PaymentController::detail/$1');
            $routes->post('create-booking-invoice/(:num)', 'PaymentController::createBookingInvoice/$1');
            $routes->post('pay-cash/(:num)', 'PaymentController::payCash/$1');
            $routes->post('create-bank-qr/(:num)', 'PaymentController::createBankQr/$1');
            $routes->post('confirm-bank-payment/(:num)', 'PaymentController::confirmBankPayment/$1');
            $routes->post('refund/(:num)', 'PaymentController::refund/$1');
            $routes->post('cancel/(:num)', 'PaymentController::cancel/$1');
            $routes->get('qr-config', 'PaymentController::qrConfig');
            $routes->post('save-qr-config', 'PaymentController::saveQrConfig');
        });

        // Audit logs routes
        $routes->group('audit-logs', ['filter' => 'permission:audit-logs.view'], function ($routes) {
            $routes->get('/', 'AuditLogController::index');
        });

        // SaaS plan routes (gói dịch vụ của tenant)
        $routes->group('plans', ['filter' => 'permission:plans.view'], function ($routes) {
            $routes->get('/', 'PlansController::index');
            $routes->post('subscribe/(:num)', 'PlansController::subscribe/$1');
        });

        // Media library routes
        $routes->group('media', ['filter' => 'permission:media.view'], function ($routes) {
            $routes->get('/', 'MediaController::index');
            $routes->post('upload', 'MediaController::upload');
            $routes->get('delete/(:num)', 'MediaController::delete/$1');
        });

        // Notification center routes
        $routes->group('notifications', ['filter' => 'permission:notifications.view'], function ($routes) {
            $routes->get('/', 'NotificationsController::index');
            $routes->get('unread-count', 'NotificationsController::unreadCount');
            $routes->get('unread', 'NotificationsController::unread');
            $routes->post('mark-read/(:num)', 'NotificationsController::markRead/$1');
            $routes->post('mark-all-read', 'NotificationsController::markAllRead');
        });

        // Profile routes
        $routes->get('profile', 'ProfileController::index');
        $routes->post('profile', 'ProfileController::update');
    });
});

// ========== REFEREE ROUTES ==========
$routes->group('referee', ['namespace' => 'App\Controllers\Referee', 'filter' => 'auth'], function ($routes) {
    $routes->group('scores', function ($routes) {
        $routes->get('/', 'Scores::index');
        $routes->get('(:num)', 'Scores::edit/$1');
        $routes->post('(:num)/start', 'Scores::start/$1');
        $routes->post('(:num)/update', 'Scores::update/$1');
        $routes->post('(:num)/finish', 'Scores::finish/$1');
    });
});

// ========== PLAYER ROUTES ==========
$routes->group('player', ['namespace' => 'App\Controllers\Player'], function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('profile', 'ProfileController::index');
    $routes->post('profile/update', 'ProfileController::update');
    $routes->get('profile/membership', 'ProfileController::membership');
    $routes->post('profile/buy-package', 'ProfileController::buyPackage');
    $routes->get('profile/cancel-membership/(:num)', 'ProfileController::cancelMembership/$1');
    $routes->get('wallet', 'WalletController::index');
    $routes->post('wallet/topup', 'WalletController::topup');
    $routes->get('ranking', 'ProfileController::ranking');

    // Player booking routes
    $routes->group('bookings', function ($routes) {
        $routes->get('/', 'BookingsController::index');
        $routes->get('create', 'BookingsController::create');
        $routes->post('create', 'BookingsController::store');
        $routes->post('get-slots', 'BookingsController::getSlots');
        $routes->get('week-availability', 'BookingsController::weekAvailability');
        $routes->get('detail/(:num)', 'BookingsController::detail/$1');
        $routes->post('cancel/(:num)', 'BookingsController::cancel/$1');
    });

    $routes->group('teams', function ($routes) {
        $routes->get('/', 'TeamsController::index');
        $routes->get('create', 'TeamsController::create');
        $routes->post('create', 'TeamsController::store');
        $routes->get('show/(:num)', 'TeamsController::show/$1');
        $routes->post('invite/(:num)', 'TeamsController::invite/$1');
        $routes->post('accept/(:num)', 'TeamsController::accept/$1');
        $routes->post('remove/(:num)/(:num)', 'TeamsController::remove/$1/$2');
    });

    $routes->group('matches', function ($routes) {
        $routes->get('/', 'MatchesController::index');
        $routes->get('create', 'MatchesController::create');
        $routes->post('create', 'MatchesController::store');
        $routes->get('show/(:num)', 'MatchesController::show/$1');
        $routes->post('join/(:num)', 'MatchesController::join/$1');
        $routes->post('confirm/(:num)', 'MatchesController::confirm/$1');
    });

    $routes->group('open-play', function ($routes) {
        $routes->get('/', 'OpenPlayController::index');
        $routes->get('create', 'OpenPlayController::create');
        $routes->post('create', 'OpenPlayController::store');
        $routes->post('join/(:num)', 'OpenPlayController::join/$1');
        $routes->post('leave/(:num)', 'OpenPlayController::leave/$1');
    });

    $routes->group('social', function ($routes) {
        $routes->get('/', 'SocialController::index');
        $routes->post('follow/(:num)', 'SocialController::follow/$1');
        $routes->post('unfollow/(:num)', 'SocialController::unfollow/$1');
        $routes->post('favorite', 'SocialController::favorite');
        $routes->post('unfavorite', 'SocialController::unfavorite');
    });

    $routes->group('coaching', function ($routes) {
        $routes->get('/', 'CoachingController::index');
        $routes->post('join/(:num)', 'CoachingController::join/$1');
        $routes->post('leave/(:num)', 'CoachingController::leave/$1');
        $routes->post('pay/(:num)', 'CoachingController::pay/$1');
    });

    $routes->get('competitions', 'CompetitionsController::index');
    $routes->post('competitions/ladder/(:num)/respond', 'CompetitionsController::ladderRespond/$1');
    $routes->post('competitions/participants/(:num)/pay', 'CompetitionsController::payEntry/$1');
    $routes->group('community', function ($routes) {
        $routes->get('/', 'CommunityController::index');
        $routes->post('store', 'CommunityController::store');
        $routes->post('(:num)/comment', 'CommunityController::comment/$1');
        $routes->post('(:num)/react', 'CommunityController::react/$1');
    });
    $routes->get('livestream', 'LivestreamController::index');
    $routes->group('growth', function ($routes) {
        $routes->get('/', 'GrowthController::index');
        $routes->post('referral/apply', 'GrowthController::applyReferral');
        $routes->post('reviews/store', 'GrowthController::review');
    });

    $routes->get('history', 'HistoryController::index');
});
