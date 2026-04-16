<?php

/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

// Load Composer's autoloader so PHP fixture classes (UserFixture, UserModel, etc.)
// can be unserialised from the request bodies sent by the Tappet JS API bridge.
require __DIR__ . '/../../../../../vendor/autoload.php';

use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserModel;

session_start();

if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
    $_SESSION['next_id'] = 1;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Validates the Authorization: Bearer header for Tappet API routes.
 * Returns true if the key matches, false otherwise.
 */
function tappetAuthorised(): bool
{
    $expected = 'Bearer test-api-key';
    $actual = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    return $actual === $expected;
}

// ─── Tappet API route: load a single fixture. ──────────────────────────────────────
if ($method === 'POST'
    && preg_match('#^/\.well-known/tappet/fixture/(.+)$#', $uri, $m)
) {
    if (!tappetAuthorised()) {
        http_response_code(401);
        print 'Unauthorised';
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true, flags: JSON_THROW_ON_ERROR);
    $fixture = unserialize($body['serialisation']);

    if (!($fixture instanceof UserFixture)) {
        http_response_code(400);
        print 'Invalid fixture';
        exit;
    }

    /** @var UserFixture $fixture */
    $id = $_SESSION['next_id']++;
    $_SESSION['users'][$id] = [
        'id'         => $id,
        'first_name' => $fixture->getFirstName(),
        'last_name'  => $fixture->getLastName(),
        'email'      => $fixture->getEmail(),
    ];

    $model = new UserModel($id);
    header('Content-Type: application/json');
    print json_encode(['serialisation' => serialize($model)], flags: JSON_THROW_ON_ERROR);
    exit;
}

// ─── Tappet API route: load multiple fixtures (bulk). ───────────────────────────────
if ($method === 'POST' && $uri === '/.well-known/tappet/fixtures') {
    if (!tappetAuthorised()) {
        http_response_code(401);
        print 'Unauthorised';
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true, flags: JSON_THROW_ON_ERROR);
    /** @var array<string, mixed> $fixturesData handle → fixture object */
    $fixturesData = unserialize($body['serialisation']);

    $models = [];
    foreach ($fixturesData as $handle => $fixture) {
        if (!($fixture instanceof UserFixture)) {
            http_response_code(400);
            print 'Invalid fixture';
            exit;
        }

        /** @var UserFixture $fixture */
        $id = $_SESSION['next_id']++;
        $_SESSION['users'][$id] = [
            'id'         => $id,
            'first_name' => $fixture->getFirstName(),
            'last_name'  => $fixture->getLastName(),
            'email'      => $fixture->getEmail(),
        ];
        $models[$handle] = new UserModel($id);
    }

    header('Content-Type: application/json');
    print json_encode(['serialisation' => serialize($models)], flags: JSON_THROW_ON_ERROR);
    exit;
}

// ─── Tappet API route: purge fixtures. ──────────────────────────────────────────────
if ($method === 'DELETE' && $uri === '/.well-known/tappet/fixtures') {
    if (!tappetAuthorised()) {
        http_response_code(401);
        print 'Unauthorised';
        exit;
    }

    $modelsToPurge = json_decode(file_get_contents('php://input'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($modelsToPurge as $entry) {
        /** @var UserModel $model */
        $model = unserialize($entry['model']);
        unset($_SESSION['users'][$model->getId()]);
    }
    $_SESSION['logged_in_user_id'] = null;
    http_response_code(204);
    exit;
}

// ─── Tappet test helper route: programmatic login. ───────────────────────────────────
if ($method === 'GET' && preg_match('#^/_tappet/auth/login/(\d+)$#', $uri, $m)) {
    $userId = (int) $m[1];
    $_SESSION['logged_in_user_id'] = $userId;
    header('Location: /users');
    exit;
}

// ─── App route: user list. ───────────────────────────────────────────────────────────
if ($method === 'GET' && $uri === '/users') {
    $flash = $_SESSION['flash'] ?? null;
    $_SESSION['flash'] = null;

    $users = $_SESSION['users'] ?? [];
    $loggedInId = $_SESSION['logged_in_user_id'] ?? null;
    $loggedIn = $loggedInId !== null ? ($users[$loggedInId] ?? null) : null;

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="utf-8"><title>User List</title></head>
    <body>
    <?php if ($flash): ?>
        <div data-ui-region="flash"
             data-flash-type="<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
    <?php if ($loggedIn): ?>
        <p>Logged in as: <?= htmlspecialchars($loggedIn['first_name'] . ' ' . $loggedIn['last_name']) ?></p>
    <?php endif; ?>
    <ul>
    <?php foreach ($users as $user): ?>
        <li>
            <a href="/users/<?= $user['id'] ?>"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></a>
        </li>
    <?php endforeach; ?>
    </ul>
    </body>
    </html>
    <?php
    exit;
}

// ─── App route: user edit form. ──────────────────────────────────────────────────────
if ($method === 'GET' && preg_match('#^/users/(\d+)$#', $uri, $m)) {
    $userId = (int) $m[1];
    $user = $_SESSION['users'][$userId] ?? null;

    if ($user === null) {
        http_response_code(404);
        print 'User not found';
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="utf-8"><title>Edit User</title></head>
    <body>
    <form method="POST" action="/users/<?= $userId ?>">
        <input type="text"
               name="first_name"
               data-ui-field="first-name"
               value="<?= htmlspecialchars($user['first_name']) ?>">
        <input type="text"
               name="last_name"
               data-ui-field="last-name"
               value="<?= htmlspecialchars($user['last_name']) ?>">
        <button type="submit" data-ui-interaction="save">Save</button>
    </form>
    </body>
    </html>
    <?php
    exit;
}

// ─── App route: save user. ───────────────────────────────────────────────────────────
if ($method === 'POST' && preg_match('#^/users/(\d+)$#', $uri, $m)) {
    $userId = (int) $m[1];

    if (!isset($_SESSION['users'][$userId])) {
        http_response_code(404);
        print 'User not found';
        exit;
    }

    $_SESSION['users'][$userId]['first_name'] = $_POST['first_name'] ?? '';
    $_SESSION['users'][$userId]['last_name'] = $_POST['last_name'] ?? '';

    $_SESSION['flash'] = [
        'type'    => 'success',
        'message' => 'User saved successfully',
    ];

    header('Location: /users');
    exit;
}

// ─── Fallback - route not found. ─────────────────────────────────────────────────────────────────
http_response_code(404);
print 'Not found';
