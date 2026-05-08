<?php
declare(strict_types=1);

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/token.php';

function get_bearer_token(): ?string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    return trim($matches[1]);
}

function require_auth(): array
{
    $config = require __DIR__ . '/../config/config.php';
    $token = get_bearer_token();
    if (!$token) {
        error_response('Authentication required.', 401);
    }

    $payload = verify_token($token, $config['app']['token_secret']);
    if (!$payload) {
        error_response('Invalid or expired token.', 401);
    }

    return $payload;
}

function require_admin(): array
{
    $user = require_auth();
    if (($user['role'] ?? 'user') !== 'admin') {
        error_response('Admin access required.', 403);
    }
    return $user;
}
