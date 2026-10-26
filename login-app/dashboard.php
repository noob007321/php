<?php
/**
 * ==========================================================================
 *  dashboard.php — the "different page" you reach after a successful login
 * ==========================================================================
 *
 *  The very first line that *does* something is require_login().
 *  If there is no valid session, the visitor is redirected to index.php and
 *  never sees this page. If there is a session, we get the user's data back.
 */

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';

$user = require_login();     // <- the lock on the door

$initial = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<main class="card card-wide">

    <header class="dash-head">
        <div class="avatar"><?= e($initial) ?></div>
        <div>
            <h1>Welcome, <?= e($user['name']) ?></h1>
            <p class="muted">You are logged in &middot; login ID <code><?= e($user['id']) ?></code></p>
        </div>
    </header>

    <div class="alert alert-success" role="status">
        This is a different page. The login ID and password matched,
        so the server sent you here with an HTTP 302 redirect.
    </div>

    <div class="grid">
        <div class="stat">
            <span class="stat-label">Login ID</span>
            <span class="stat-value"><?= e($user['id']) ?></span>
        </div>
        <div class="stat">
            <span class="stat-label">Display name</span>
            <span class="stat-value"><?= e($user['name']) ?></span>
        </div>
        <div class="stat">
            <span class="stat-label">Logged in at</span>
            <span class="stat-value"><?= e($user['login_time']) ?></span>
        </div>
        <div class="stat">
            <span class="stat-label">Session ID</span>
            <span class="stat-value small"><?= e(session_id()) ?></span>
        </div>
    </div>

    <details class="dump">
        <summary>What is inside <code>$_SESSION</code> right now?</summary>
        <p class="muted small">
            PHP stores these values on the server, in a file named after the
            session ID. The browser only holds the ID in its <code>PHPSESSID</code>
            cookie — never the values themselves.
        </p>
<pre><?php print_r($_SESSION); ?></pre>
    </details>

    <div class="actions">
        <a class="btn btn-ghost" href="logout.php">Log out</a>
        <a class="btn" href="index.php">Login page</a>
    </div>

    <footer class="card-foot">
        Request: <code><?= e($_SERVER['REQUEST_METHOD']) ?> <?= e($_SERVER['REQUEST_URI']) ?></code>
        &middot; <a class="foot-link" href="http-errors.php">HTTP status codes</a>
    </footer>

</main>

</body>
</html>
