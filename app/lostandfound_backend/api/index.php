<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/token.php';
require_once __DIR__ . '/services/MatcherService.php';

header('Access-Control-Allow-Origin: ' . ($config['app']['cors_origin'] ?? '*'));
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = get_pdo();
$matcherService = new MatcherService($pdo, $config);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($scriptDir !== '' && $scriptDir !== '/') {
    $route = preg_replace('#^' . preg_quote($scriptDir, '#') . '#', '', $uri) ?: '/';
} else {
    $route = $uri;
}
$route = '/' . trim($route, '/');
$method = $_SERVER['REQUEST_METHOD'];

function public_user_payload(array $row): array {
    return [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'role' => $row['role'],
    ];
}

try {
    if ($method === 'POST' && $route === '/auth/register') {
        $body = get_json_input();
        $fullName = trim((string) ($body['full_name'] ?? ''));
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');
        $role = in_array(($body['role'] ?? 'user'), ['user', 'admin'], true) ? $body['role'] : 'user';

        if ($fullName === '' || $email === '' || $password === '') {
            error_response('full_name, email, and password are required.', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_response('Invalid email address.', 422);
        }
        if (strlen($password) < 6) {
            error_response('Password must be at least 6 characters.', 422);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            error_response('An account with that email already exists.', 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $insert->execute([$fullName, $email, $passwordHash, $role]);

        json_response([
            'message' => 'Account created successfully.',
            'user' => [
                'id' => (int) $pdo->lastInsertId(),
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
            ],
        ], 201);
    }

    if ($method === 'POST' && $route === '/auth/login') {
        $body = get_json_input();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            error_response('Email and password are required.', 422);
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            error_response('Invalid email or password.', 401);
        }

        $token = generate_token($user, $config['app']['token_secret'], $config['app']['token_ttl_seconds']);
        json_response([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => public_user_payload($user),
        ]);
    }

    if ($method === 'GET' && $route === '/items') {
        $keyword = trim((string) ($_GET['keyword'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        $sql = 'SELECT i.*, u.full_name AS owner_name FROM items i JOIN users u ON u.id = i.user_id WHERE 1=1';
        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)';
            $like = '%' . $keyword . '%';
            array_push($params, $like, $like, $like);
        }
        if ($category !== '') {
            $sql .= ' AND i.category = ?';
            $params[] = $category;
        }
        if ($status !== '') {
            $sql .= ' AND i.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY i.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
    }

    if ($method === 'GET' && preg_match('#^/items/(\d+)$#', $route, $matches)) {
        $itemId = (int) $matches[1];
        $stmt = $pdo->prepare('SELECT i.*, u.full_name AS owner_name FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) {
            error_response('Item not found.', 404);
        }
        json_response($item);
    }

    if ($method === 'GET' && $route === '/items/my-items') {
        $authUser = require_auth();
        $stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([(int) $authUser['sub']]);
        json_response($stmt->fetchAll());
    }

    if ($method === 'POST' && ($route === '/items/lost' || $route === '/items/found')) {
        $authUser = require_auth();
        $body = get_json_input();

        $title = trim((string) ($body['title'] ?? ''));
        $category = trim((string) ($body['category'] ?? ''));
        $description = trim((string) ($body['description'] ?? ''));
        $location = trim((string) ($body['location'] ?? ''));
        $status = $route === '/items/lost' ? 'lost' : 'found';
        $dateLost = $status === 'lost' ? (($body['date_lost'] ?? null) ?: null) : null;
        $dateFound = $status === 'found' ? (($body['date_found'] ?? null) ?: null) : null;

        if ($title === '' || $category === '' || $description === '' || $location === '') {
            error_response('title, category, description, and location are required.', 422);
        }

        $insert = $pdo->prepare(
            'INSERT INTO items (user_id, title, category, description, location, date_lost, date_found, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            (int) $authUser['sub'],
            $title,
            $category,
            $description,
            $location,
            $dateLost,
            $dateFound,
            $status,
        ]);

        $itemId = (int) $pdo->lastInsertId();
        $matcherService->updateMatchesForItem($itemId);

        $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        json_response([
            'message' => ucfirst($status) . ' item created successfully.',
            'item' => $stmt->fetch(),
        ], 201);
    }

    if ($method === 'POST' && preg_match('#^/claims/(\d+)$#', $route, $matches)) {
        $authUser = require_auth();
        $itemId = (int) $matches[1];
        $body = get_json_input();

        $claimantName = trim((string) ($body['claimant_name'] ?? ''));
        $claimantEmail = strtolower(trim((string) ($body['claimant_email'] ?? '')));
        $reason = trim((string) ($body['reason'] ?? ''));

        if ($claimantName === '' || $claimantEmail === '' || $reason === '') {
            error_response('claimant_name, claimant_email, and reason are required.', 422);
        }
        if (!filter_var($claimantEmail, FILTER_VALIDATE_EMAIL)) {
            error_response('Invalid claimant email address.', 422);
        }

        $itemStmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch();
        if (!$item) {
            error_response('Item not found.', 404);
        }

        $claimStmt = $pdo->prepare(
            'INSERT INTO claims (item_id, user_id, claimant_name, claimant_email, reason, status)
             VALUES (?, ?, ?, ?, ?, ?)' 
        );
        $claimStmt->execute([
            $itemId,
            (int) $authUser['sub'],
            $claimantName,
            $claimantEmail,
            $reason,
            'pending',
        ]);

        json_response(['message' => 'Claim submitted successfully.'], 201);
    }

    if ($method === 'GET' && $route === '/admin/claims') {
        require_admin();
        $stmt = $pdo->query(
            'SELECT c.*, i.title AS item_title
             FROM claims c
             JOIN items i ON i.id = c.item_id
             WHERE c.status = "pending"
             ORDER BY c.created_at DESC'
        );
        json_response($stmt->fetchAll());
    }

    if ($method === 'GET' && preg_match('#^/matches/(\d+)$#', $route, $matches)) {
        $itemId = (int) $matches[1];
        $stmt = $pdo->prepare(
            'SELECT im.score, i.*
             FROM item_matches im
             JOIN items i ON i.id = im.matched_item_id
             WHERE im.source_item_id = ?
             ORDER BY im.score DESC, im.created_at DESC'
        );
        $stmt->execute([$itemId]);
        json_response($stmt->fetchAll());
    }

    error_response('Route not found.', 404);
} catch (PDOException $e) {
    error_response('Database error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    error_response('Server error: ' . $e->getMessage(), 500);
}
