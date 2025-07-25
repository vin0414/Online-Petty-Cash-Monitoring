<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->post('auth','AuthController::auth');
$routes->get('/logout','AuthController::logout');
$routes->get('/auto-login/(:any)','AuthController::autoLogin/$1');
//functions
$routes->post('save','FileController::save');
$routes->post('edit','FileController::edit');
//get
$routes->get('fetch-assign','Home::fetchAssign');
$routes->get('fetch-user','Home::fetchUser');
$routes->get('fetch-department','Home::fetchDepartment');
$routes->get('for-review','FileController::forReview');
$routes->get('view-details','FileController::viewDetails');
$routes->get('approve','FileController::approveFile');
$routes->get('fetch-item','FileController::fetchItem');
$routes->get('unsettle','FileController::unSettle');
//post
$routes->post('save-user','Home::saveUser');
$routes->post('save-assign','Home::saveAssign');
$routes->post('save-department','Home::saveDepartment');
$routes->post('remove-assignment','Home::removeAssignment');
$routes->post('remove-user','Home::removeUser');
$routes->post('accept','FileController::accept');
$routes->post('reject','FileController::reject');
$routes->post('hold','FileController::hold');
$routes->post('release','FileController::release');
$routes->post('add-item','FileController::addItem');
$routes->post('close-item','FileController::closeItem');
$routes->post('add-amount','FileController::addAmount');
$routes->post('settle','FileController::settleItem');
//compute
$routes->get('unsettle-balance','Compute::unsettle');
$routes->get('settle-balance','Compute::settle');
$routes->get('unliquidated','Compute::unliquidated');
$routes->get('cash-on-hand','Compute::cashOnHand');
$routes->get('total-amount','Compute::total');
//print
$routes->get('print/(:any)','FileController::print/$1');

$routes->group('',['filter'=>'AlreadyLoggedIn'],function($routes)
{
    $routes->get('/', 'Home::index');
});

$routes->group('',['filter'=>'AuthCheck'],function($routes)
{
    $routes->get('dashboard', 'Home::dashboard');
    $routes->get('new','Home::newRequest');
    $routes->get('edit','Home::editRequest');
    $routes->get('re-apply/(:any)','Home::reApply/$1');
    $routes->get('manage','Home::manageRequest');
    $routes->get('review','Home::reviewRequest');
    $routes->get('manage-cash','Home::manageCash');
    $routes->get('configure','Home::configure');
    $routes->get('account','Home::account');
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
