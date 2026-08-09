<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Player Auth
$routes->group('player', function($routes) {
    $routes->get('/', 'Player\DashboardController::index');
    $routes->get('login', 'Player\AuthController::login');
    $routes->post('login', 'Player\AuthController::doLogin');
    $routes->get('register', 'Player\AuthController::register');
    $routes->post('register', 'Player\AuthController::doRegister');
    $routes->get('logout', 'Player\AuthController::logout');

    // Protected player routes
    $routes->group('', ['filter' => ['player_auth']], function($routes) {
        $routes->get('bookings', 'Player\BookingsController::index');
        $routes->get('booking/create', 'Player\BookingsController::create');
        $routes->get('booking/(:num)', 'Player\BookingsController::detail/$1');
        $routes->post('booking/checkin/(:num)', 'Player\BookingsController::checkin/$1');
        $routes->post('booking/cancel/(:num)', 'Player\BookingsController::cancel/$1');
        $routes->get('booking/qr/(:num)', 'Player\BookingsController::qr/$1');

        $routes->get('tournaments', 'Player\TournamentController::index');
        $routes->get('tournament/(:num)', 'Player\TournamentController::detail/$1');
        $routes->post('tournament/register/(:num)', 'Player\TournamentController::register/$1');

        $routes->get('teams', 'Player\TeamController::index');
        $routes->get('team/create', 'Player\TeamController::create');
        $routes->post('team/store', 'Player\TeamController::store');
        $routes->post('team/join/(:num)', 'Player\TeamController::join/$1');

        $routes->get('wallet', 'Player\WalletController::index');
        $routes->post('wallet/topup', 'Player\WalletController::topup');
        $routes->get('wallet/history', 'Player\WalletController::history');

        $routes->get('profile', 'Player\ProfileController::index');
        $routes->post('profile/update', 'Player\ProfileController::update');
        $routes->get('profile/membership', 'Player\ProfileController::membership');

        $routes->get('ranking', 'Player\RankingController::index');

        $routes->get('notifications', 'Player\NotificationController::index');
        $routes->post('notifications/read/(:num)', 'Player\NotificationController::read/$1');
    });
});

// Player API v1
$routes->group('api/v1/player', ['filter' => ['player_auth_api']], function($routes) {
    $routes->get('profile', 'Api\PlayerApi::profile');
    $routes->get('bookings', 'Api\PlayerApi::bookings');
    $routes->get('tournaments', 'Api\PlayerApi::tournaments');
    $routes->get('teams', 'Api\PlayerApi::teams');
    $routes->get('notifications', 'Api\PlayerApi::notifications');
});
