<?php

$routes->group('pos', ['filter' => ['auth', 'tenant', 'permission:pos.access']], function($routes) {
    // POS Counter
    $routes->add('/', 'Admin\PosController@index');
    $routes->add('counter', 'Admin\PosController@index');

    // Order actions
    $routes->post('addItem/(:num)', 'Admin\PosController@addItem/$1');
    $routes->post('removeItem/(:num)/(:num)', 'Admin\PosController@removeItem/$1/$2');
    $routes->post('updateItem/(:num)', 'Admin\PosController@updateItem/$1');
    $routes->post('checkout/(:num)', 'Admin\PosController@checkout/$1');
    $routes->post('cancel/(:num)', 'Admin\PosController@cancel/$1');
    $routes->get('getOrder/(:num)', 'Admin\PosController@getOrder/$1');

    // Attach booking/player
    $routes->post('attachBooking/(:num)', 'Admin\PosController@attachBooking/$1');
    $routes->post('attachPlayer/(:num)', 'Admin\PosController@attachPlayer/$1');

    // Search
    $routes->get('searchProducts/(:num)', 'Admin\PosController@searchProducts/$1');
    $routes->get('searchBookings', 'Admin\PosController@searchBookings');
    $routes->get('searchPlayers', 'Admin\PosController@searchPlayers');

    // Product management
    $routes->get('products', 'Admin\PosController@products');
    $routes->post('createProduct', 'Admin\PosController@createProduct');
    $routes->post('updateProduct', 'Admin\PosController@updateProduct');
    $routes->get('getProduct/(:num)', 'Admin\PosController@getProduct/$1');

    // Inventory management
    $routes->get('inventory', 'Admin\PosController@inventory');
    $routes->post('importStock', 'Admin\PosController@importStock');
    $routes->post('adjustStock', 'Admin\PosController@adjustStock');
    $routes->get('getStock/(:num)', 'Admin\PosController@getStock/$1');
    $routes->get('inventoryHistory', 'Admin\PosController@inventoryHistory');

    // Reports
    $routes->get('salesReport', 'Admin\PosController@salesReport');
});
