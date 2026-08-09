<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ========== PUBLIC ROUTES ==========
$routes->get('/', 'Home::index');
$routes->get('/login', 'AuthController::login');
$routes->post('/login', 'AuthController::loginPost');
$routes->get('/logout', 'AuthController::logout');
$routes->get('/locale/switch/(:any)', 'AuthController::switchLocale/$1');
$routes->get('/live-scores', 'LiveScores::index', ['namespace' => 'App\Controllers\Public']);
$routes->get('/live-scores/bracket', 'LiveScores::bracket', ['namespace' => 'App\Controllers\Public']);
$routes->get('/live-scores/tv', 'LiveScores::tv', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments', 'Tournaments::list', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments/(:segment)', 'Tournaments::detail/$1', ['namespace' => 'App\Controllers\Public']);
$routes->get('/tournaments/(:segment)/register', 'Tournaments::register/$1', ['namespace' => 'App\Controllers\Public']);
$routes->post('/tournaments/(:segment)/register', 'Tournaments::submitRegistration/$1', ['namespace' => 'App\Controllers\Public']);

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

        // Facility API routes
        $routes->get('facilities', 'FacilityApi::index');
        $routes->get('facilities/(:num)', 'FacilityApi::show/$1');
        $routes->get('facilities/(:num)/dashboard', 'FacilityApi::dashboard/$1');
        $routes->get('facilities/(:num)/branches', 'FacilityApi::branches/$1');
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
        $routes->post('facilities/device/(:num)/toggle', 'FacilityApi::toggleDevice/$1');

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

        // Player mobile API routes
        $routes->get('player/profile', 'PlayerApi::profile');
        $routes->post('player/update-profile', 'PlayerApi::updateProfile');
        $routes->get('player/wallet', 'PlayerApi::wallet');
        $routes->get('player/ranking', 'PlayerApi::ranking');
    });
});

// ========== ADMIN ROUTES ==========
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function ($routes) {
    // Auth protected routes
    $routes->group('', ['filter' => 'auth'], function ($routes) {
        $routes->get('dashboard', 'DashboardController::index');

        $routes->group('ops', function ($routes) {
            $routes->get('available-courts', 'OpsAjaxController::availableCourts');
            $routes->match(['get', 'post'], 'pricing-test', 'OpsAjaxController::pricingTest');
            $routes->get('booking-drawer/(:num)', 'OpsAjaxController::bookingDrawer/$1');
            $routes->get('court-drawer/(:num)', 'OpsAjaxController::courtDrawer/$1');
            $routes->get('court-pricing-rules/(:num)', 'OpsAjaxController::courtPricingRules/$1');
            $routes->get('reschedule-preview/(:num)', 'OpsAjaxController::reschedulePreview/$1');
        });

        // UI foundation demo routes
        $routes->group('ui-demo', function ($routes) {
            $routes->get('dashboard', 'UiDemoController::dashboard');
            $routes->get('list', 'UiDemoController::list');
            $routes->get('form', 'UiDemoController::form');
            $routes->get('detail', 'UiDemoController::detail');
        });

        // Facility routes (Cluster management)
        $routes->group('facilities', function ($routes) {
            $routes->get('/', 'FacilitiesController::index');
            $routes->get('create', 'FacilitiesController::create');
            $routes->post('create', 'FacilitiesController::store');
            $routes->get('edit/(:num)', 'FacilitiesController::edit/$1');
            $routes->post('update/(:num)', 'FacilitiesController::update/$1');
            $routes->get('delete/(:num)', 'FacilitiesController::delete/$1');
            $routes->get('dashboard/(:num)', 'FacilitiesController::dashboard/$1');
            $routes->get('branches/(:num)', 'FacilitiesController::branches/$1');
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
        $routes->group('tenants', function ($routes) {
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
        $routes->group('branches', function ($routes) {
            $routes->get('/', 'BranchController::index');
            $routes->get('create', 'BranchController::create');
            $routes->post('create', 'BranchController::store');
            $routes->get('edit/(:num)', 'BranchController::edit/$1');
            $routes->post('update/(:num)', 'BranchController::update/$1');
            $routes->get('delete/(:num)', 'BranchController::delete/$1');
        });

        // User routes
        $routes->group('users', function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->get('create', 'UserController::create');
            $routes->post('create', 'UserController::store');
            $routes->get('edit/(:num)', 'UserController::edit/$1');
            $routes->post('update/(:num)', 'UserController::update/$1');
            $routes->get('delete/(:num)', 'UserController::delete/$1');
        });

        // Role routes
        $routes->group('roles', function ($routes) {
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
        $routes->group('settings', function ($routes) {
            $routes->get('/', 'SettingController::index');
            $routes->post('update', 'SettingController::update');
        });

        // Court routes
        $routes->group('courts', function ($routes) {
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
        $routes->group('bookings', function ($routes) {
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

        // Score input & live tournament scoring
        $routes->group('scores', function ($routes) {
            $routes->get('/', 'Scores::index');
            $routes->get('(:num)', 'Scores::edit/$1');
            $routes->post('(:num)/start', 'Scores::start/$1');
            $routes->post('(:num)/update', 'Scores::update/$1');
            $routes->post('(:num)/finish', 'Scores::finish/$1');
        });

        // Tournament setup routes
        $routes->group('tournaments', function ($routes) {
            $routes->get('/', 'Tournaments::index');
            $routes->get('create', 'Tournaments::create');
            $routes->post('store', 'Tournaments::store');
            $routes->get('edit/(:num)', 'Tournaments::edit/$1');
            $routes->post('update/(:num)', 'Tournaments::update/$1');
            $routes->get('show/(:num)', 'Tournaments::show/$1');
            $routes->get('registrations/(:num)', 'Tournaments::registrations/$1');
            $routes->post('registrations/(:num)/approve', 'Tournaments::approveRegistration/$1');
            $routes->post('registrations/(:num)/reject', 'Tournaments::rejectRegistration/$1');
            $routes->post('open/(:num)', 'Tournaments::open/$1');
            $routes->post('close/(:num)', 'Tournaments::close/$1');
            $routes->post('cancel/(:num)', 'Tournaments::cancel/$1');
        });

        // Dynamic pricing routes
        $routes->group('pricing-rules', function ($routes) {
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
        $routes->group('players', function ($routes) {
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

        // Club, team and social match routes
        $routes->group('clubs', function ($routes) {
            $routes->get('/', 'ClubsController::index');
            $routes->get('create', 'ClubsController::create');
            $routes->post('store', 'ClubsController::store');
            $routes->get('edit/(:num)', 'ClubsController::edit/$1');
            $routes->post('update/(:num)', 'ClubsController::update/$1');
            $routes->get('delete/(:num)', 'ClubsController::delete/$1');
        });

        $routes->group('teams', function ($routes) {
            $routes->get('/', 'TeamsController::index');
            $routes->get('show/(:num)', 'TeamsController::show/$1');
            $routes->post('status/(:num)', 'TeamsController::status/$1');
        });

        $routes->group('matches', function ($routes) {
            $routes->get('/', 'MatchRequestsController::index');
            $routes->get('show/(:num)', 'MatchRequestsController::show/$1');
            $routes->post('approve/(:num)', 'MatchRequestsController::approve/$1');
            $routes->post('cancel/(:num)', 'MatchRequestsController::cancel/$1');
            $routes->post('convert/(:num)', 'MatchRequestsController::convert/$1');
        });

        // Membership routes
        $routes->group('memberships', function ($routes) {
            $routes->get('/', 'MembershipsController::index');
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
        $routes->group('tournaments/scheduler', function ($routes) {
            $routes->get('/', 'TournamentSchedulerController::index');
            $routes->post('auto-schedule', 'TournamentSchedulerController::autoSchedule');
            $routes->post('rerun-unlocked', 'TournamentSchedulerController::rerunUnlocked');
            $routes->post('lock/(:num)', 'TournamentSchedulerController::lock/$1');
            $routes->post('unlock/(:num)', 'TournamentSchedulerController::unlock/$1');
            $routes->post('team-group', 'TournamentSchedulerController::moveTeam');
        });

        // Audit logs routes
        $routes->group('audit-logs', function ($routes) {
            $routes->get('/', 'AuditLogController::index');
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

    $routes->get('history', 'HistoryController::index');
});
