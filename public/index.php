<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../inc/config.php';

use Slim\Http\ServerRequest as Request;
use Slim\Http\Response as Response;

$app = \Slim\Factory\AppFactory::create();

$app->get('/', function (Request $request, Response $response) {
    return (new \Paw\Controller\IndexController($request, $response))->get();
});
$app->get('/listings', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->listings();
});
$app->get('/add_listing', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->add_listing_form();
});
$app->post('/add_listing_submit', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->add_listing_submit();
});
$app->get('/listing', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->show_listing();
});
$app->get('/edit_listing', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->edit_listing_form();
});
$app->post('/edit_listing_submit', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->edit_listing_submit();
});
$app->get('/delete_listing', function (Request $request, Response $response) {
    return (new \Paw\Controller\ListingController($request, $response))->delete_listing();
});
$app->get('/profile', function (Request $request, Response $response) {
    return (new \Paw\Controller\ProfileController($request, $response))->show_profile();
});
$app->get('/edit_profile', function (Request $request, Response $response) {
    return (new \Paw\Controller\ProfileController($request, $response))->edit_profile_form();
});
$app->post('/edit_profile_submit', function (Request $request, Response $response) {
    return (new \Paw\Controller\ProfileController($request, $response))->edit_profile_submit();
});
$app->get('/login', function (Request $request, Response $response) {
    return (new \Paw\Controller\LoginController($request, $response))->index();
});
$app->post('/login/qr_auth', function (Request $request, Response $response) {
    return (new \Paw\Controller\LoginController($request, $response))->qr_auth();
});
$app->get('/logout', function (Request $request, Response $response) {
    return (new \Paw\Controller\LoginController($request, $response))->logout();
});
$app->get('/cron', function (Request $request, Response $response) {
    return (new \Paw\Controller\CronController($request, $response))->run();
});
$app->run();