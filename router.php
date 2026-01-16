<?php

use Paheko\Web\Router;
use Paheko\UserException;
use Paheko\Plugin\OIDC\AuthorizationManager;

// 1. Vérification de l'URI et de la méthode HTTP
$uri = Router::getRequestURI();
$lastPart = basename(rtrim($uri, '/'));

if ($lastPart !== "refresh") {
    error_log("Invalid URI: $uri (expected 'refresh' as last part)");
    throw new UserException('Invalid endpoint.', 404);
}

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    error_log("Invalid HTTP method: {$_SERVER['REQUEST_METHOD']} (expected GET)");
    throw new UserException('Method not allowed.', 405);
}

// 2. Validation des paramètres GET
$requiredParams = [
    'search_id' => ['type' => 'int', 'message' => 'search_id must be an integer.'],
    'ts' => ['type' => 'int', 'message' => 'ts must be an integer.'],
    'sig' => ['type' => 'hex', 'message' => 'sig must be a hexadecimal string.']
];

$params = [];
foreach ($requiredParams as $param => $rules) {
    if (!isset($_GET[$param])) {
        error_log("Missing parameter: $param");
        throw new UserException('Invalid request parameters', 400);
    }

    $value = $_GET[$param];
    switch ($rules['type']) {
        case 'int':
            if (!is_numeric($value)) {
                error_log("Invalid parameter: $param (must be an integer, got: $value)");
                throw new UserException('Invalid request parameters', 400);
            }
            $params[$param] = (int)$value;
            break;
        case 'hex':
            if (!ctype_xdigit($value)) {
                error_log("Invalid parameter: $param (must be a hex string, got: $value)");
                throw new UserException('Invalid request parameters', 400);
            }
            $params[$param] = $value;
            break;
        default:
            error_log("Invalid parameter type for: $param");
            throw new UserException('Internal server error.', 500);
    }
}

// Check timestamp window
if (abs(time() - $params['ts']) > 300) {
    error_log("Invalid timestamp value (out of window).");
    throw new UserException('Invalid request parameters (ts).', 400);
}

// Check hash
$expected = hash_hmac(
    'sha256',
    $params['search_id'] . '|' . $params['ts'],
    $this->getConfig("HMAC_SECRET")
);

if (!hash_equals($expected, $params['sig'])){
    error_log("Invalid signature.");
    throw new UserException('Invalid request parameters (sig check).', 400);
}

// Launch the search members refreshing
$plugin = new AuthorizationManager($this);
$sid = $plugin->refresh_search_members($params['search_id']);
if ($sid === $params['search_id']){
    http_response_code(204);
} else {
    error_log("Error during search members refreshing.");
    throw new UserException('Internal server error (refresh).', 500);
}
