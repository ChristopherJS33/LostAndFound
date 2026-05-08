<?php
declare(strict_types=1);

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string
{
    $padding = 4 - (strlen($data) % 4);
    if ($padding < 4) {
        $data .= str_repeat('=', $padding);
    }
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function generate_token(array $user, string $secret, int $ttlSeconds): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
        'sub' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
        'iat' => time(),
        'exp' => time() + $ttlSeconds,
    ];

    $headerB64 = base64url_encode(json_encode($header));
    $payloadB64 = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true);

    return $headerB64 . '.' . $payloadB64 . '.' . base64url_encode($signature);
}

function verify_token(string $token, string $secret): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerB64, $payloadB64, $sigB64] = $parts;
    $expected = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true);
    $actual = base64url_decode($sigB64);

    if (!hash_equals($expected, $actual)) {
        return null;
    }

    $payload = json_decode(base64url_decode($payloadB64), true);
    if (!is_array($payload) || !isset($payload['exp']) || time() >= (int) $payload['exp']) {
        return null;
    }

    return $payload;
}
