<?php
ob_start();

require __DIR__ . "/../vendor/autoload.php";

/**
 * BOOTSTRAP
 */

use CoffeeCode\Router\Router;

header('Access-Control-Allow-Origin: *');
header ("Access-Control-Expose-Headers: Content-Length, X-JSON");
header ("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header ("Access-Control-Allow-Headers: Content-Type, Authorization, Accept, Accept-Language, X-Authorization, action, email, password, token");
header('Access-Control-Max-Age: 86400');
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header("Access-Control-Allow-Headers: zX-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization, action, email, password, token");
    header("HTTP/1.1 200 OK");
    return;
}

$version_api = "'\'v1";
if(count(explode('/', $_SERVER['REQUEST_URI'])) > 3){
    $version_api = "'\'" . explode('/', $_SERVER['REQUEST_URI'])[3];
}
$version_api = (!empty($version_api) ? str_replace("'", "", $version_api) : "");

/**
 * API ROUTES
 * index
 */
$route = new Router(url(), ":");
$route->namespace("Source\App\Api" . $version_api);

/** auth */
$route->post("/v1/signup","SignUp:register");
$route->post("/v1/me","Users:index");

/** order */
$route->get("/v1/orders","Orders:list");
$route->post("/v1/order","Orders:create");
$route->put("/v1/order/{id}","Orders:update");

/** pay */
$route->get("/v1/pay/auth","Pay:auth");

/**
 * ROUTE
 */
$route->dispatch();

/**
 * ERROR REDIRECT
 */
if ($route->error()) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(404);

    echo json_encode([
        "errors" => [
            "type " => "endpoint_not_found",
            "message" => "Não foi possível processar a requisição"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

ob_end_flush();