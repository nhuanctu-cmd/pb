<?php

$routes->group('admin/payments', ['filter' => ['auth', 'tenant', 'permission:payment.access']], function($routes) {
    $routes->get('/', 'Admin\PaymentController::index');
    $routes->get('detail/(:num)', 'Admin\PaymentController::detail/$1');
    $routes->post('pay-cash/(:num)', 'Admin\PaymentController::payCash/$1');
    $routes->post('create-bank-qr/(:num)', 'Admin\PaymentController::createBankQr/$1');
    $routes->post('confirm-bank-payment/(:num)', 'Admin\PaymentController::confirmBankPayment/$1');
    $routes->post('refund/(:num)', 'Admin\PaymentController::refund/$1');
    $routes->post('cancel/(:num)', 'Admin\PaymentController::cancel/$1');
    $routes->get('qr-config', 'Admin\PaymentController::qrConfig');
    $routes->post('save-qr-config', 'Admin\PaymentController::saveQrConfig');
});
